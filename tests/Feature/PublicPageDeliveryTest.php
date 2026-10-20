<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPageDeliveryTest extends TestCase
{
    public function test_public_pages_are_stateless_and_have_page_specific_seo_metadata(): void
    {
        $home = $this->get('/')->assertOk();
        $home->assertHeader('Cache-Control', 'max-age=0, must-revalidate, public');
        $home->assertHeader('Vercel-CDN-Cache-Control', 'public, s-maxage=30, stale-while-revalidate=60');
        $this->assertFalse($home->headers->has('Set-Cookie'));
        $home->assertSee('<link rel="canonical" href="'.route('home').'">', false);
        $home->assertSee('src="/assets/design/sd-ceria-nusantara-campus.webp"', false);
        $home->assertSee('data-src="/assets/design/asset-03.webp"', false);

        $teachers = $this->get('/guru-staf')->assertOk();
        $this->assertFalse($teachers->headers->has('Set-Cookie'));
        $teachers->assertSee('<link rel="canonical" href="'.route('teachers').'">', false);
        $teachers->assertSee('name="description" content="Di kelas, di perpustakaan', false);

        $contact = $this->get('/kontak')->assertOk();
        $this->assertTrue($contact->headers->has('Set-Cookie'));
        $this->assertFalse($contact->headers->has('Vercel-CDN-Cache-Control'));
    }

    public function test_sitemap_and_robots_expose_only_public_content(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $sitemap->headers->get('Content-Type'));
        $sitemap->assertSee('<loc>'.route('gallery').'</loc>', false);
        $sitemap->assertDontSee('/pendaftaran', false);
        $this->assertFalse($sitemap->headers->has('Set-Cookie'));

        $robots = $this->get('/robots.txt')->assertOk();
        $robots->assertSee('Sitemap: '.route('sitemap'), false);
        $robots->assertSee('Disallow: /admin/', false);
        $this->assertFalse($robots->headers->has('Set-Cookie'));
    }
}
