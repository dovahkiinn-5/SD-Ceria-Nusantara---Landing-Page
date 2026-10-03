<?php

namespace Tests\Feature;

use App\Contracts\DocumentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\FakeFirestoreFileStorage;
use Tests\TestCase;

class SchoolWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(FakeFirestoreFileStorage::class, new FakeFirestoreFileStorage);
        $this->app->instance(\App\Services\FirestoreFileStorage::class, $this->app->make(FakeFirestoreFileStorage::class));
    }

    public function test_all_public_pages_render(): void
    {
        foreach (['/','/tentang-kami','/program','/fasilitas','/guru-staf','/galeri','/kontak','/privasi','/syarat-ketentuan','/pendaftaran/1','/admin/login'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_registration_steps_cannot_be_skipped_and_fields_are_validated(): void
    {
        $this->get('/pendaftaran/4')->assertRedirect('/pendaftaran/1');
        $this->post('/pendaftaran/langkah/1',[])->assertSessionHasErrors(['child_name','nickname','birth_date','gender']);
        $this->post('/pendaftaran/langkah/3',[])->assertRedirect('/pendaftaran/1');
        $this->assertSame(0,app(DocumentStore::class)->count('applications'));
    }

    private function child(): array
    {
        return ['child_name'=>'Anak Uji','nickname'=>'Uji','birth_date'=>'2019-04-10','gender'=>'Perempuan'];
    }

    private function parentData(): array
    {
        return ['parent_name'=>'Wali Uji','relationship'=>'Ibu','phone'=>'+6281234567890','email'=>'wali@example.test','address'=>'Alamat uji'];
    }

    private function completeDraft(): string
    {
        $this->post('/pendaftaran/langkah/1',$this->child())->assertRedirect('/pendaftaran/2');
        $this->post('/pendaftaran/langkah/2',$this->parentData())->assertRedirect('/pendaftaran/3');
        $this->post('/pendaftaran/langkah/3',[
            'birth_certificate'=>UploadedFile::fake()->create('akta.pdf',10,'application/pdf'),
            'family_card'=>UploadedFile::fake()->create('kk.pdf',10,'application/pdf'),
            'photo'=>UploadedFile::fake()->create('foto.png',10,'image/png'),
        ])->assertRedirect('/pendaftaran/4');
        return session('registration.reference');
    }

    public function test_complete_registration_is_private_and_repeat_submit_is_idempotent(): void
    {
        $reference=$this->completeDraft();
        $this->assertSame(0,app(DocumentStore::class)->count('applications'));
        $this->get('/pendaftaran/4')->assertOk()->assertSee('Anak Uji')->assertSee('wali@example.test');
        $this->post('/pendaftaran/kirim',[])->assertSessionHasErrors('consent');
        $this->post('/pendaftaran/kirim',['consent'=>'1'])->assertRedirect('/pendaftaran/berhasil');
        $this->post('/pendaftaran/kirim',['consent'=>'1'])->assertRedirect('/pendaftaran/berhasil');
        $store=app(DocumentStore::class);
        $this->assertSame(1,$store->count('applications'));
        $record=$store->get('applications',$reference);
        $this->assertSame('baru',$record['status']);
        $this->assertTrue(app(FakeFirestoreFileStorage::class)->exists($record['documents']['photo']['path']));
        $this->get('/pendaftaran/berhasil')->assertOk()->assertSee($reference);
        $this->get('/admin/berkas/'.$reference.'/photo')->assertRedirect('/admin/login');
        $this->get('/storage/'.$record['documents']['photo']['path'])->assertNotFound();
        $this->get('/admin/pendaftar/export')->assertRedirect('/admin/login');
    }

    public function test_invalid_file_types_and_oversized_files_are_rejected(): void
    {
        $this->post('/pendaftaran/langkah/1',$this->child());
        $this->post('/pendaftaran/langkah/2',$this->parentData());
        $this->post('/pendaftaran/langkah/3',[
            'birth_certificate'=>UploadedFile::fake()->create('code.php',10,'application/x-php'),
            'family_card'=>UploadedFile::fake()->create('kk.pdf',1025,'application/pdf'),
            'photo'=>UploadedFile::fake()->create('photo.svg',10,'image/svg+xml'),
        ])->assertSessionHasErrors(['birth_certificate','family_card','photo']);
        $this->assertSame([],app(FakeFirestoreFileStorage::class)->objects);
    }

    public function test_registration_accepts_three_documents_at_the_one_megabyte_limit(): void
    {
        $this->post('/pendaftaran/langkah/1',$this->child());
        $this->post('/pendaftaran/langkah/2',$this->parentData());

        $this->post('/pendaftaran/langkah/3',[
            'birth_certificate'=>UploadedFile::fake()->create('akta.pdf',1024,'application/pdf'),
            'family_card'=>UploadedFile::fake()->create('kk.pdf',1024,'application/pdf'),
            'photo'=>UploadedFile::fake()->image('foto.png')->size(1024),
        ])->assertRedirect('/pendaftaran/4');

        $this->assertCount(3,app(FakeFirestoreFileStorage::class)->objects);
    }

    public function test_public_media_is_served_from_firestore(): void
    {
        $storage = app(FakeFirestoreFileStorage::class);
        $storage->put('media/school.png', 'image-data', 'image/png');

        $this->get('/media/school.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertContent('image-data');
    }

    public function test_replacing_upload_removes_previous_file(): void
    {
        $this->completeDraft();
        $old=session('registration.documents.photo.path');
        $this->post('/pendaftaran/langkah/3',['photo'=>UploadedFile::fake()->create('baru.png',10,'image/png')])->assertRedirect('/pendaftaran/4');
        $storage = app(FakeFirestoreFileStorage::class);
        $this->assertFalse($storage->exists($old));
        $this->assertTrue($storage->exists(session('registration.documents.photo.path')));
    }

    public function test_visit_requires_consent_and_weekday_and_is_saved(): void
    {
        $base=['name'=>'Wali Uji','email'=>'wali@example.test','phone'=>'081234567890','date'=>now()->next('Monday')->toDateString()];
        $this->post('/kunjungan',$base)->assertSessionHasErrors('consent');
        $this->post('/kunjungan',array_merge($base,['consent'=>'1','date'=>now()->next('Sunday')->toDateString()]))->assertSessionHasErrors('date');
        $this->post('/kunjungan',$base+['consent'=>'1'])->assertRedirect('/kunjungan/berhasil');
        $this->get('/kunjungan/berhasil')->assertOk()->assertSee('Permintaan kunjungan diterima');
        $this->assertSame(1,app(DocumentStore::class)->count('visits'));
    }

    public function test_document_pagination_does_not_skip_or_repeat_rows(): void
    {
        $store=app(DocumentStore::class);
        for($i=0;$i<62;$i++) $store->create('visits',str_pad((string)$i,4,'0',STR_PAD_LEFT),['name'=>'Visit '.$i]);
        $ids=[];$cursor=null;
        do { $page=$store->page('visits',$cursor); $ids=array_merge($ids,array_column($page['items'],'id'));$cursor=$page['next']; } while($cursor);
        $this->assertCount(62,$ids);
        $this->assertCount(62,array_unique($ids));
        $this->assertSame('0061',$ids[0]);
        $this->assertFalse($store->create('visits','0061',['name'=>'overwrite']));
        $this->assertSame('Visit 61',$store->get('visits','0061')['name']);
    }
}
