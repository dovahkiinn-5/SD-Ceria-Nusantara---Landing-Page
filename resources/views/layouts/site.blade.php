<!doctype html>
<html lang="id" data-theme="light">
<head>
    @php
        $indexedPages = ['home', 'about', 'program', 'facilities', 'teachers', 'gallery', 'contact', 'privacy', 'terms'];
        $isIndexedPage = in_array($page ?? '', $indexedPages, true);
        $description = match ($page ?? '') {
            'teachers' => $site['teachers']['directory_description'] ?? $site['teachers']['description'],
            'privacy' => 'Kebijakan privasi dan pengelolaan data SD Ceria Nusantara.',
            'terms' => 'Syarat dan ketentuan pendaftaran SD Ceria Nusantara.',
            default => $site[$page ?? 'home']['description'] ?? $site['home']['description'],
        };
        $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $description)));
        $pageTitle = trim(preg_replace('/\s+/u', ' ', $__env->yieldContent('title', $site['settings']['school_name'])));
        $canonical = $isIndexedPage ? route($page) : null;
        $shareImage = \App\Support\PublicImage::url($site['home']['campus_image'] ?? '/assets/design/sd-ceria-nusantara-campus.png');
    @endphp
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    @if($isIndexedPage)
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ url($shareImage) }}">
    <meta name="twitter:card" content="summary_large_image">
    @else
    <meta name="robots" content="noindex,nofollow">
    @endif
    <meta name="theme-color" content="#095d89">
    <title>{{ $pageTitle }}</title>
    <link rel="icon" href="{{ \App\Support\PublicImage::favicon($site['settings']['logo']) }}" type="image/png">
    <link rel="preload" href="/assets/fonts/Poppins-Regular.ttf" as="font" type="font/ttf" crossorigin>
    @if(($page ?? '') === 'home')<link rel="preload" as="image" href="{{ \App\Support\PublicImage::url($site['home']['campus_image'] ?? '/assets/design/sd-ceria-nusantara-campus.png') }}" fetchpriority="high">@endif
    <link rel="stylesheet" href="/css/site.css">
    <link rel="stylesheet" href="/css/appearance.css">
    <link rel="stylesheet" href="/css/hero-motion.css">
    <link rel="stylesheet" href="/css/announcement.css">
    @if(($page ?? '') === 'teachers')<link rel="stylesheet" href="/css/team-directory.css">@endif
    @if(($page ?? '') === 'gallery')<link rel="stylesheet" href="/css/gallery.css">@endif
    @if(($page ?? '') === 'registration')<link rel="stylesheet" href="/css/registration.css">@endif
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('scn-theme')||'light'}catch(e){}</script>
    <script src="/js/language.js" defer></script>
    <script src="/js/site.js" defer></script>
</head>
<body class="page-{{ $page ?? 'registration' }}">
<a class="skip-link" href="#main">Lewati ke konten</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ $site['settings']['school_name'] }}, beranda">
            <span class="brand-emblem" aria-hidden="true">
                <img src="{{ \App\Support\PublicImage::url($site['settings']['logo']) }}" alt="" width="62" height="62">
            </span>
            @php($brandWords = preg_split('/\s+/', trim($site['settings']['school_name'])))
            <span>{{ implode(' ', array_slice($brandWords, 0, 2)) }}@if(count($brandWords)>2)<small>{{ implode(' ', array_slice($brandWords, 2)) }}</small>@endif</span>
        </a>
        <nav id="navigation" aria-label="Navigasi utama">
            @foreach(['home'=>'Beranda','about'=>'Tentang Kami','program'=>'Program','facilities'=>'Fasilitas','teachers'=>'Guru & Staf','gallery'=>'Galeri','contact'=>'Kontak','announcement'=>'Pengumuman'] as $key=>$label)
            <a href="{{ route($key) }}" @if(($page ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="site-preferences" aria-label="Pengaturan tampilan">
            <button class="preference-button" type="button" data-theme-toggle aria-label="Aktifkan mode gelap" title="Mode gelap"><span aria-hidden="true">☾</span></button>
            <button class="preference-button language-toggle" type="button" data-language-toggle aria-label="Switch to English" title="English">EN</button>
        </div>
        <a class="button small" href="{{ route('registration', 1) }}">Daftar Sekarang</a>
        <button class="menu-toggle" aria-controls="navigation" aria-expanded="false" aria-label="Buka menu"><span></span><span></span><span></span></button>
    </div>
</header>
<main id="main">
    @yield('content')
    @unless(isset($hideCta))
    <section class="cta outer {{ ($page ?? '') === 'home' ? 'home-cta' : '' }}">
        <div>@if(($page ?? '') === 'home')<p class="eyebrow">SIAP BERKENALAN DENGAN KAMI?</p>@endif
        <h2>{{ ($page ?? '') === 'home' ? 'Mari mulai langkah pertama bersama.' : 'Siap mengenal '.$site['settings']['school_name'].' lebih dekat?' }}</h2>
        <p>{{ ($page ?? '') === 'home' ? 'Daftar secara online atau jadwalkan kunjungan ke sekolah.' : 'Hubungi kami atau mulai proses pendaftaran online.' }}</p></div>
        <div class="cta-actions"><a class="button" href="{{ route('registration', 1) }}">Daftar Sekarang</a>
        @if(($page ?? '') === 'home')<a class="button outline" href="{{ route('contact') }}#kunjungan">Jadwalkan Kunjungan</a>@endif</div>
    </section>
    @endunless
</main>
<footer class="site-footer"><div class="container footer-inner">
    <div class="footer-brand"><img src="{{ \App\Support\PublicImage::url($site['settings']['logo']) }}" width="58" height="58" loading="lazy" alt=""><div><strong>{{ $site['settings']['school_name'] }}</strong><p>{{ $site['settings']['tagline'] }}</p></div></div>
    <div class="footer-contact"><p><a href="mailto:{{ $site['settings']['email'] }}">{{ $site['settings']['email'] }}</a> • <a href="https://wa.me/{{ preg_replace('/\D/', '', $site['settings']['whatsapp']) }}" target="_blank" rel="noopener">WA {{ $site['settings']['whatsapp'] }}</a></p><p>{{ $site['settings']['address'] }}</p><p>{{ $site['settings']['hours'] }}</p></div>
    @if(($page ?? '') === 'home')<div class="footer-bottom"><span>© {{ date('Y') }} {{ $site['settings']['school_name'] }}. Hak cipta dilindungi.</span><span><a href="{{ route('privacy') }}">Privasi</a> • <a href="{{ route('terms') }}">Syarat & Ketentuan</a></span></div>@endif
</div></footer>
<a class="whatsapp-float" data-whatsapp-float href="https://wa.me/{{ preg_replace('/\D/', '', $site['settings']['whatsapp']) }}" target="_blank" rel="noopener" aria-label="Tanya via WhatsApp" title="Tanya via WhatsApp">
    <img src="/assets/design/whatsapp.svg" alt="" width="38" height="38">
    <span>Tanya via WhatsApp</span>
</a>
<dialog id="image-dialog" class="image-dialog"><button class="dialog-close" type="button" aria-label="Tutup gambar">×</button><img alt=""><p></p></dialog>
@stack('scripts')
</body></html>
