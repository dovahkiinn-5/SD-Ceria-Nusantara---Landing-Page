<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentStore;
use App\Services\FirestoreFileStorage;
use App\Services\SchoolContent;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginForm() { return view('admin.login'); }
    public function login(Request $request)
    {
        $credentials = $request->validate(['email'=>'required|email|max:190','password'=>'required|string|max:200']);
        if (! Auth::attempt($credentials)) throw ValidationException::withMessages(['email'=>'Email atau kata sandi tidak sesuai.']);
        $request->session()->regenerate();
        return redirect()->intended(route('admin.dashboard'));
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function dashboard(DocumentStore $store)
    {
        return view('admin.dashboard', [
            'applications'=>$store->page('applications', null, 5)['items'],
            'totals'=>['Pendaftar'=>$store->count('applications'),'Kunjungan'=>$store->count('visits'),'Admin'=>$store->count('admins')],
        ]);
    }
    public function records(Request $request, DocumentStore $store, string $collection)
    {
        abort_unless(in_array($collection, ['applications','visits']), 404);
        $request->validate(['cursor'=>'nullable|string|max:4000','reference'=>'nullable|string|max:100']);
        $results = $request->filled('reference') && $collection === 'applications'
            ? ['items'=>array_values(array_filter([$store->get($collection, trim($request->string('reference')))])), 'next'=>null]
            : $store->page($collection, $request->input('cursor'));
        return view('admin.records', compact('collection','results'));
    }
    public function record(DocumentStore $store, string $collection, string $id)
    {
        abort_unless(in_array($collection, ['applications','visits']), 404);
        $record = $store->get($collection, $id);
        abort_unless($record, 404);
        return view('admin.record', compact('collection','record'));
    }
    public function updateRecord(Request $request, DocumentStore $store, string $collection, string $id)
    {
        abort_unless(in_array($collection, ['applications','visits']), 404);
        $record = $store->get($collection, $id);
        abort_unless($record, 404);
        $statuses = $collection === 'applications' ? ['baru','ditinjau','diterima','ditolak'] : ['baru','dikonfirmasi','selesai','dibatalkan'];
        $data = $request->validate(['status'=>['required',Rule::in($statuses)],'admin_notes'=>'nullable|string|max:5000']);
        $record['status'] = $data['status'];
        $record['admin_notes'] = $data['admin_notes'];
        $record['updated_at'] = now()->toIso8601String();
        $record['updated_by'] = Auth::id();
        $store->put($collection, $id, $record);
        return back()->with('status','Status berhasil diperbarui.');
    }
    public function deleteRecord(Request $request, DocumentStore $store, FirestoreFileStorage $storage, string $collection, string $id)
    {
        abort_unless($request->user()->role === 'owner', 403);
        abort_unless(in_array($collection, ['applications','visits']), 404);
        $record = $store->get($collection, $id);
        abort_unless($record, 404);
        foreach ($record['documents'] ?? [] as $file) $storage->delete($file['path']);
        $store->delete($collection, $id);
        return redirect()->route('admin.records',$collection)->with('status','Data dan berkas berhasil dihapus.');
    }
    public function document(DocumentStore $store, FirestoreFileStorage $storage, string $id, string $field)
    {
        abort_unless(in_array($field,['birth_certificate','family_card','photo']),404);
        $record = $store->get('applications',$id);
        $file = $record['documents'][$field] ?? null;
        $download = $file ? $storage->get($file['path']) : null;
        abort_unless($download,404);
        return response()->streamDownload(
            static function () use ($download) { echo $download['contents']; },
            Str::ascii(basename($file['name'])),
            [
                'Content-Type' => $download['content_type'],
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
    public function export(DocumentStore $store)
    {
        return response()->streamDownload(function () use ($store) {
            $stream = fopen('php://output', 'w');
            fwrite($stream,"\xEF\xBB\xBF");
            fputcsv($stream,['Nomor','Nama anak','Tanggal lahir','Nama orang tua','WhatsApp','Email','Status','Tanggal daftar'],',','"','');
            $cursor = null;
            do {
                $page = $store->page('applications',$cursor,100);
                foreach ($page['items'] as $row) {
                    $cells = [$row['reference'],$row['child']['child_name'],$row['child']['birth_date'],$row['parent']['parent_name'],$row['parent']['phone'],$row['parent']['email'],$row['status'],$row['created_at']];
                    $cells = array_map(fn ($value) => preg_match('/^[\s]*[=+@\-\t\r]/u',(string)$value) ? "'".$value : $value,$cells);
                    fputcsv($stream,$cells,',','"','');
                }
                $cursor = $page['next'];
            } while ($cursor);
            fclose($stream);
        },'pendaftar-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function content(SchoolContent $content, string $section = 'home')
    {
        $all = $content->all();
        abort_unless(isset($all[$section]),404);
        $fields = Arr::dot($all[$section]);
        return view('admin.content',compact('all','section','fields'));
    }
    public function updateContent(Request $request, SchoolContent $content, FirestoreFileStorage $storage, string $section)
    {
        $all = $content->all();
        abort_unless(isset($all[$section]),404);
        $fields = Arr::dot($all[$section]);
        $request->validate(['values'=>'required|array','uploads.*'=>'nullable|file|mimes:jpg,jpeg,png,webp|max:1024']);
        $data = [];
        foreach ($fields as $key => $value) {
            $new = $request->input('values')[$key] ?? $value;
            Validator::make(['value'=>$new],['value'=>'nullable|string|max:10000'])->validate();
            if (preg_match('/(^image$|_image$|^logo$|\.photo$)/',$key)) {
                if (! preg_match('#^/(?:assets/design|media)/[a-zA-Z0-9_.-]+\.(png|jpe?g|webp)$#',(string)$new)) {
                    throw ValidationException::withMessages(['values'=>'Gunakan gambar lokal yang diunggah atau aset desain yang tersedia.']);
                }
                $index = array_search($key,array_keys($fields));
                if ($file = $request->file('uploads.'.$index)) {
                    $path = 'media/'.Str::uuid().'.'.$file->guessExtension();
                    $storage->putUploadedFile($path, $file);
                    $new = '/'.$path;
                }
            }
            if ($key === 'email') Validator::make(['email'=>$new],['email'=>'required|email'])->validate();
            if ($key === 'whatsapp') Validator::make(['phone'=>$new],['phone'=>['required','regex:/^\+?[0-9 ()-]{9,25}$/']])->validate();
            Arr::set($data,$key,$new ?? '');
        }
        $content->save($section,$data);
        return back()->with('status','Konten berhasil disimpan. Perubahan sudah tampil di website.');
    }
    public function accounts(Request $request, DocumentStore $store)
    {
        abort_unless($request->user()->role === 'owner',403);
        $request->validate(['cursor'=>'nullable|string|max:4000']);
        return view('admin.accounts',['results'=>$store->page('admins',$request->input('cursor'))]);
    }
    public function createAccount(Request $request, DocumentStore $store)
    {
        abort_unless($request->user()->role === 'owner',403);
        $data = $request->validate(['name'=>'required|string|max:120','email'=>'required|email|max:190',
            'password'=>['required','confirmed',Password::min(12)->letters()->numbers()], 'role'=>['required',Rule::in(['owner','editor'])]]);
        $data['email'] = mb_strtolower(trim($data['email']));
        $data['password'] = Hash::make($data['password']);
        $data['active'] = true;
        $data['created_at'] = now()->toIso8601String();
        if (! $store->create('admins',hash('sha256',$data['email']),$data)) throw ValidationException::withMessages(['email'=>'Email admin sudah terdaftar.']);
        return back()->with('status','Akun admin berhasil dibuat.');
    }
    public function toggleAccount(Request $request, DocumentStore $store, string $id)
    {
        abort_unless($request->user()->role === 'owner' && $request->user()->id !== $id,403);
        $record = $store->get('admins',$id);
        abort_unless($record,404);
        $record['active'] = ! $record['active'];
        $store->put('admins',$id,$record);
        return back()->with('status','Status akun diperbarui.');
    }
    public function changePassword(Request $request, DocumentStore $store)
    {
        $data = $request->validate(['current_password'=>'required|string','password'=>['required','confirmed',Password::min(12)->letters()->numbers()]]);
        if (! Hash::check($data['current_password'],$request->user()->password)) throw ValidationException::withMessages(['current_password'=>'Kata sandi saat ini tidak sesuai.']);
        $record = $store->get('admins',Auth::id());
        $record['password'] = Hash::make($data['password']);
        $store->put('admins',Auth::id(),$record);
        $request->user()->password = $record['password'];
        $request->session()->regenerate();
        return back()->with('status','Kata sandi diperbarui.');
    }
}
