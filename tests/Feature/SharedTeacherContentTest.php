<?php

namespace Tests\Feature;

use App\Contracts\DocumentStore;
use App\Services\SchoolContent;
use App\Services\TeacherDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SharedTeacherContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_shared_directory_updates_public_cards_organization_subjects_and_hero(): void
    {
        $content = app(SchoolContent::class);
        $teachers = $content->defaults()['teachers'];
        $teachers['people'][0]['name'] = 'Kepala sekolah dari dashboard';
        $teachers['people'][0]['photo'] = '/media/published-head.jpg';
        $teachers['subjects'][0]['by'] = 'Pengajar dari dashboard';
        $teachers['directory_title'] = 'Judul dari dashboard <script>unsafe</script>';
        $teachers['demo_profiles'] = false;
        $content->save('teachers', $teachers);
        $this->get('/guru-staf')->assertOk()->assertSee('Kepala sekolah dari dashboard')
            ->assertSee('/media/published-head.jpg')->assertSee('Pengajar dari dashboard')
            ->assertSee('Judul dari dashboard <script>unsafe</script>')->assertDontSee('<script>unsafe</script>', false)
            ->assertDontSee('Contoh profil sekolah.');
        $this->get('/')->assertOk()->assertSee('Kepala sekolah dari dashboard')->assertSee('/media/published-head.jpg');
        $this->get('/tentang-kami')->assertOk()->assertSee('Kepala sekolah dari dashboard');
        $this->assertArrayNotHasKey('team', $content->all()['teachers']);
    }

    public function test_shorter_lists_do_not_resurrect_default_people_and_legacy_documents_have_fallbacks(): void
    {
        $content = app(SchoolContent::class);
        app(DocumentStore::class)->put('content', 'teachers', ['team'=>[['name'=>'Beranda saja','role'=>'Guru','photo'=>'/assets/design/teacher-1.png']]]);
        $this->assertArrayNotHasKey('team', $content->all()['teachers']);
        $this->get('/guru-staf')->assertOk()->assertSee('Ratna Puspitasari');
        $teachers = TeacherDirectory::defaults();
        $teachers['people'] = [array_replace($teachers['people'][0], ['name'=>'Satu profil saja'])];
        $teachers['structure'] = ['head'=>0,'branches'=>[]];
        $teachers['subjects'] = [];
        $content->save('teachers', $teachers);
        $this->get('/guru-staf')->assertOk()->assertSee('Satu profil saja')->assertDontSee('Arif Pranata');
        $this->assertCount(1, $content->all()['teachers']['people']);
        $this->assertSame([], $content->all()['teachers']['structure']['branches']);
    }

    public function test_legacy_duplicates_never_override_the_chart_and_saves_remove_them(): void
    {
        $content = app(SchoolContent::class);
        $teachers = $content->defaults()['teachers'];
        $teachers['people'][0]['name'] = 'Kepala resmi struktur';
        $teachers['directory'] = $teachers['people'];
        $teachers['directory'][0]['name'] = 'Profil lama berbeda';
        $teachers['team'] = [$teachers['directory'][0]];
        app(DocumentStore::class)->put('content', 'teachers', $teachers);
        foreach (['/', '/tentang-kami', '/guru-staf'] as $path) {
            $this->get($path)->assertOk()->assertSee('Kepala resmi struktur')->assertDontSee('Profil lama berbeda');
        }
        $teachers['structure']['head'] = 1;
        $content->save('teachers', $teachers);
        $this->assertArrayNotHasKey('directory', app(DocumentStore::class)->get('content', 'teachers'));
        $this->assertArrayNotHasKey('team', app(DocumentStore::class)->get('content', 'teachers'));
        $this->assertSame($teachers['people'][1]['name'], TeacherDirectory::principal($content->all()['teachers'])['name']);
        $this->get('/tentang-kami')->assertSee($teachers['people'][1]['name'])->assertDontSee('Kepala resmi struktur');
    }

    public function test_invalid_structure_is_rejected_without_writing(): void
    {
        $data = TeacherDirectory::defaults();
        $data['structure']['head'] = 500;
        try { app(SchoolContent::class)->save('teachers', $data); $this->fail('Invalid reference accepted.'); }
        catch (ValidationException) { $this->assertNull(app(DocumentStore::class)->get('content', 'teachers')); }
    }
}
