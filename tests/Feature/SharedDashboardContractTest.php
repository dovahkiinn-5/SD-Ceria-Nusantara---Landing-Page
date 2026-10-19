<?php

namespace Tests\Feature;

use App\Contracts\DocumentStore;
use App\Services\FirestoreFileStorage;
use App\Services\SchoolContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\FakeFirestoreFileStorage;
use Tests\TestCase;

class SharedDashboardContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(FirestoreFileStorage::class, new FakeFirestoreFileStorage);
    }

    private function draft(): array
    {
        $documents = [];
        foreach (['birth_certificate','family_card','child_identity_card','photo'] as $field) {
            $path = 'applications/CN-2026-CONTRACT/'.$field.'.png';
            app(FirestoreFileStorage::class)->put($path, 'document', 'image/png');
            $documents[$field] = ['path'=>$path, 'name'=>$field.'.png', 'size'=>8];
        }
        return ['reference'=>'CN-2026-CONTRACT', 'token'=>'private-token', 'completed'=>3,
            'child'=>['child_name'=>'Anak kontrak','birth_date'=>'2019-04-10'],
            'parent'=>['parent_name'=>'Wali kontrak','email'=>'wali@example.test'], 'documents'=>$documents];
    }

    public function test_transfer_is_stored_in_dashboard_fields_and_proof_is_private(): void
    {
        $draft = $this->draft();
        $settings = app(SchoolContent::class)->defaults()['settings'];
        app(DocumentStore::class)->put('content','settings',[...$settings,'fee'=>'Rp350.000']);
        $this->withSession(['registration'=>$draft])->get('/pendaftaran/4')->assertOk()->assertSee('Rp350.000');
        $this->post('/pendaftaran/kirim', ['consent'=>1,'payment_method'=>'transfer',
            'payment_proof'=>UploadedFile::fake()->createWithContent('bukti.pdf','%PDF-1.4 proof')])->assertSessionHasNoErrors()->assertRedirect('/pendaftaran/berhasil');
        $record = app(DocumentStore::class)->get('applications',$draft['reference']);
        $this->assertSame('transfer',$record['payment_method']);
        $this->assertSame(350000,$record['payment']['amount']);
        $this->assertSame($record['payment']['proof'],$record['documents']['payment_proof']);
        $this->assertSame('application/pdf',$record['documents']['payment_proof']['mime']);
        $path = $record['documents']['payment_proof']['path'];
        $this->assertTrue(app(FirestoreFileStorage::class)->exists($path));
        $this->get('/admin/berkas/'.$draft['reference'].'/payment_proof')->assertRedirect('/admin/login');
        // A stale session retry cannot delete the already submitted transfer proof.
        $this->withSession(['registration'=>$draft])->post('/pendaftaran/kirim',['consent'=>1,'payment_method'=>'offline'])->assertRedirect('/pendaftaran/berhasil');
        $this->assertSame('transfer',app(DocumentStore::class)->get('applications',$draft['reference'])['payment_method']);
        $this->assertTrue(app(FirestoreFileStorage::class)->exists($path));
        $this->actingAs(new \Illuminate\Auth\GenericUser(['id'=>'owner','password'=>'test-password-hash','remember_token'=>'','role'=>'owner','active'=>true,'name'=>'Admin','email'=>'admin@example.test']));
        $this->get('/admin/berkas/'.$draft['reference'].'/payment_proof')->assertDownload('bukti.pdf')->assertStreamedContent('%PDF-1.4 proof');
    }

    public function test_offline_has_no_proof_and_transfer_cannot_skip_proof(): void
    {
        $this->withSession(['registration'=>$this->draft()]);
        $this->post('/pendaftaran/kirim',['consent'=>1,'payment_method'=>'transfer'])->assertSessionHasErrors('payment_proof');
        $this->post('/pendaftaran/kirim',['consent'=>1,'payment_method'=>'offline',
            'payment_proof'=>UploadedFile::fake()->createWithContent('proof.pdf','%PDF-1.4')])->assertSessionHasErrors('payment_proof');
        $this->post('/pendaftaran/kirim',['consent'=>1,'payment_method'=>'offline'])->assertSessionHasNoErrors();
        $record = app(DocumentStore::class)->get('applications','CN-2026-CONTRACT');
        $this->assertSame('offline',$record['payment_method']);
        $this->assertArrayNotHasKey('payment_proof',$record['documents']);
    }

    public function test_all_public_sections_refresh_after_dashboard_writes_and_cache_expiry(): void
    {
        $content = app(SchoolContent::class);
        $content->all(); // Warm the cache before an external dashboard write.
        foreach (['settings'=>['school_name','Sekolah Sinkron'], 'home'=>['title','Beranda Sinkron'],
            'about'=>['story_title','Profil Sinkron'], 'program'=>['title','Program Sinkron'],
            'facilities'=>['title','Fasilitas Sinkron'], 'gallery'=>['title','Galeri Sinkron'],
            'contact'=>['title','Kontak Sinkron'], 'teachers'=>['directory_title','Guru Sinkron']] as $section=>[$key,$value]) {
            app(DocumentStore::class)->put('content',$section,[...$content->defaults()[$section],$key=>$value]);
        }
        $this->assertNotSame('Beranda Sinkron',$content->all()['home']['title']);
        $this->travel(6)->seconds();
        foreach (['/'=>'Beranda Sinkron','/tentang-kami'=>'Profil Sinkron','/program'=>'Program Sinkron',
            '/fasilitas'=>'Fasilitas Sinkron','/galeri'=>'Galeri Sinkron','/kontak'=>'Kontak Sinkron','/guru-staf'=>'Guru Sinkron'] as $url=>$text) {
            $this->get($url)->assertOk()->assertSee($text)->assertSee('Sekolah Sinkron');
        }
    }

    public function test_added_profiles_and_subject_assignments_are_not_replaced_by_defaults(): void
    {
        $content = app(SchoolContent::class);
        $teachers = $content->defaults()['teachers'];
        $teachers['people'][] = [...$teachers['people'][0], 'name'=>'Guru baru dari dashboard'];
        $teachers['subjects'][0]['title'] = 'PJOK';
        $teachers['subjects'][0]['by'] = 'Pengajar pilihan dashboard';
        $content->save('teachers',$teachers);
        $this->get('/guru-staf')->assertOk()->assertSee('Guru baru dari dashboard')->assertSee('Pengajar pilihan dashboard');
        $this->assertCount(count($teachers['people']),$content->all()['teachers']['people']);
    }

    public function test_correction_is_not_reported_as_rejection(): void
    {
        app(DocumentStore::class)->put('applications','CN-2026-CONTRACT',[
            'reference'=>'CN-2026-CONTRACT','child'=>['child_name'=>'Anak','birth_date'=>'2019-04-10'],
            'status'=>'perlu_perbaikan','admin_notes'=>'Unggah ulang KK yang jelas.',
        ]);
        $this->post('/pengumuman',['reference'=>'CN-2026-CONTRACT','birth_date'=>'2019-04-10'])->assertOk()
            ->assertSee('PERLU PERBAIKAN')->assertSee('Unggah ulang KK yang jelas.')->assertDontSee('TIDAK LULUS');
    }
}
