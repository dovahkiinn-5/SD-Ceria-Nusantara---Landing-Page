<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentStore;
use App\Services\SchoolContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    public function store(Request $request, DocumentStore $store)
    {
        $data = $request->validate([
            'name'=>'required|string|max:120','phone'=>['required','string','regex:/^\+?[0-9 ()-]{9,25}$/'],
            'email'=>'required|email|max:190','date'=>'required|date|after_or_equal:today|before:'.now()->addYear()->toDateString(),
            'notes'=>'nullable|string|max:1000','consent'=>'accepted',
        ]);
        if (\Carbon\Carbon::parse($data['date'])->isWeekend()) {
            throw ValidationException::withMessages(['date'=>'Pilih hari Senin–Jumat untuk kunjungan sekolah.']);
        }
        $id = (string) Str::ulid();
        $store->create('visits', $id, $data + ['created_at'=>now()->toIso8601String(),'status'=>'baru']);
        $request->session()->flash('visit_success', true);
        return redirect()->route('visit.success');
    }
    public function success(Request $request, SchoolContent $content)
    {
        if (! $request->session()->has('visit_success')) return redirect()->route('contact');
        return view('registration.visit-success', ['site'=>$content->all(),'page'=>'contact']);
    }
}
