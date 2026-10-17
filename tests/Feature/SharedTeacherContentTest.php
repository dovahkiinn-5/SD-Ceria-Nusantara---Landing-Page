<?php

namespace Tests\Feature;

use App\Services\SchoolContent;
use App\Services\TeacherDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SharedTeacherContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_and_hero_read_the_shared_content_instead_of_local_profiles(): void
    {
        Http::preventStrayRequests();
        $content = app(SchoolContent::class);
        $teachers = $content->defaults()['teachers'] + TeacherDirectory::defaults();
        $teachers['people'][1]['name'] = 'KEPALA DARI DASHBOARD';
        $teachers['people'][1]['photo'] = '/media/kepala-baru.jpg';
        $teachers['structure']['head'] = 1;
        $teachers['directory_title'] = '<script>Judul dari dashboard</script>';
        $teachers['subjects'][0]['by'] = 'Tim baru dari dashboard';
        $teachers['demo_profiles'] = false;
        $content->save('teachers',$teachers);
        $this->get('/guru-staf')->assertOk()->assertSee('KEPALA DARI DASHBOARD')
            ->assertSee('/media/kepala-baru.jpg',false)->assertSee('Tim baru dari dashboard')
            ->assertSee('&lt;script&gt;Judul dari dashboard&lt;/script&gt;',false)
            ->assertDontSee('<script>Judul dari dashboard</script>',false)
            ->assertDontSee('Contoh profil sekolah.');
    }

    public function test_short_and_empty_published_lists_do_not_restore_default_cards(): void
    {
        Http::preventStrayRequests();
        $content = app(SchoolContent::class);
        $content->save('teachers',['team'=>[['name'=>'Satu anggota','role'=>'Guru','photo'=>'/media/guru.jpg']]]);
        $content->save('gallery',['news'=>[]]);
        $site = $content->all();
        $this->assertCount(1,$site['teachers']['team']);
        $this->assertCount(30,$site['teachers']['people']);
        $this->assertSame([],$site['gallery']['news']);
        $this->get('/')->assertOk();
        $this->get('/galeri')->assertOk();
    }

    public function test_first_hero_slide_uses_the_campus_photo_from_the_dashboard(): void
    {
        Http::preventStrayRequests();
        app(SchoolContent::class)->save('home',['campus_image'=>'/media/kampus-baru.jpg']);
        $this->get('/')->assertOk()->assertSee('src="/media/kampus-baru.jpg"',false);
    }
}
