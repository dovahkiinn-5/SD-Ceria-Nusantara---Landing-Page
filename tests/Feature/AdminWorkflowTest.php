<?php

namespace Tests\Feature;

use App\Contracts\DocumentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;
    private string $email='owner@example.test';
    private string $password='Secret-Test-12345';

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentStore::class)->create('admins',hash('sha256',$this->email),[
            'email'=>$this->email,'name'=>'Pemilik Uji','password'=>Hash::make($this->password),'role'=>'owner','active'=>true,
        ]);
    }
    private function login(): void
    {
        $this->post('/admin/login',['email'=>$this->email,'password'=>$this->password])->assertRedirect('/admin');
    }
    public function test_login_admin_pages_and_logout(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login',['email'=>$this->email,'password'=>'incorrect'])->assertSessionHasErrors('email');
        $this->login();
        foreach(['/admin','/admin/data/applications','/admin/data/visits','/admin/konten/home','/admin/akun','/admin/profil'] as $path) $this->get($path)->assertOk();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->get('/admin')->assertRedirect('/admin/login');
    }
    public function test_content_changes_are_visible_and_html_is_escaped(): void
    {
        $this->login();
        $this->put('/admin/konten/home',['values'=>['title'=>'Judul baru <script>alert(1)</script>']])->assertRedirect();
        $this->get('/')->assertOk()->assertSee('Judul baru <script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>',false);
        $this->put('/admin/konten/home',['values'=>['image'=>'https://example.test/image.png']])->assertSessionHasErrors('values');
    }
    public function test_editor_cannot_manage_admin_accounts_or_delete_applications(): void
    {
        $store=app(DocumentStore::class);
        $record=$store->get('admins',hash('sha256',$this->email));$record['role']='editor';$store->put('admins',$record['id'],$record);
        $this->login();
        $this->get('/admin/akun')->assertForbidden();
        $this->post('/admin/akun',[])->assertForbidden();
        $this->delete('/admin/data/applications/anything')->assertForbidden();
    }
    public function test_owner_can_create_account_and_disable_it_but_not_self(): void
    {
        $this->login();
        $this->post('/admin/akun',['name'=>'Editor','email'=>'editor@example.test','password'=>'Editor-Password-123','password_confirmation'=>'Editor-Password-123','role'=>'editor'])->assertRedirect();
        $store=app(DocumentStore::class);$id=hash('sha256','editor@example.test');
        $this->assertTrue(Hash::check('Editor-Password-123',$store->get('admins',$id)['password']));
        $this->patch('/admin/akun/'.$id)->assertRedirect();
        $this->assertFalse($store->get('admins',$id)['active']);
        $this->patch('/admin/akun/'.hash('sha256',$this->email))->assertForbidden();
        $this->post('/admin/logout');
        $this->post('/admin/login',['email'=>'editor@example.test','password'=>'Editor-Password-123'])->assertSessionHasErrors('email');
    }
    public function test_admin_can_review_download_export_and_delete_registration(): void
    {
        Storage::fake('local');Storage::disk('local')->put('applications/test/photo.png','test-image');
        $store=app(DocumentStore::class);
        $store->put('applications','CN-TEST',['reference'=>'CN-TEST','child'=>['child_name'=>'=DANGEROUS()','birth_date'=>'2019-01-01'],'parent'=>['parent_name'=>'Wali','phone'=>'+628123456789','email'=>'wali@example.test'],'created_at'=>now()->toIso8601String(),'status'=>'baru','documents'=>['photo'=>['path'=>'applications/test/photo.png','name'=>'foto.png']]]);
        $this->login();
        $this->get('/admin/data/applications/CN-TEST')->assertOk();
        $this->patch('/admin/data/applications/CN-TEST',['status'=>'diterima','admin_notes'=>'Lengkap'])->assertRedirect();
        $this->assertSame('diterima',$store->get('applications','CN-TEST')['status']);
        $this->patch('/admin/data/applications/CN-TEST',['status'=>'unknown'])->assertSessionHasErrors('status');
        $this->get('/admin/berkas/CN-TEST/photo')->assertDownload('foto.png');
        $csv=$this->get('/admin/pendaftar/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=DANGEROUS()",$csv);
        $this->delete('/admin/data/applications/CN-TEST')->assertRedirect('/admin/data/applications');
        $this->assertNull($store->get('applications','CN-TEST'));
        Storage::disk('local')->assertMissing('applications/test/photo.png');
    }
    public function test_password_change_preserves_current_session_and_hashes_password(): void
    {
        $this->login();$this->get('/admin');
        $this->put('/admin/profil',['current_password'=>$this->password,'password'=>'Changed-Password-123','password_confirmation'=>'Changed-Password-123'])->assertRedirect();
        $this->get('/admin/profil')->assertOk();
        $this->assertTrue(Hash::check('Changed-Password-123',app(DocumentStore::class)->get('admins',hash('sha256',$this->email))['password']));
    }
}
