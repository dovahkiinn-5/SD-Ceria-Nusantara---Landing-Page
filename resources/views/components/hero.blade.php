@props(['data','home'=>false,'contact'=>false])
<section class="hero outer {{ $home ? 'hero-home' : '' }} {{ $contact ? 'hero-contact' : '' }}">
    <div class="hero-decoration" aria-hidden="true"></div>
    <div class="hero-copy"><p class="hero-eyebrow">{{ $data['eyebrow'] }}</p><h1>{{ $data['title'] }}</h1><p class="hero-description">@if(isset($data['tablet_description']))<span class="desktop-only">{{ $data['description'] }}</span><span class="tablet-only">{{ $data['tablet_description'] }}</span>@else{{ $data['description'] }}@endif</p>
    <div class="hero-actions"><a class="button" href="{{ route('registration',1) }}">Daftar Sekarang</a>@if($home)<a class="button white" href="{{ route('about') }}">Lihat Profil Sekolah</a>@endif</div></div>
    <figure class="hero-image {{ $home ? 'hero-slider' : '' }}" @if($home) data-hero-slider @endif>
        @if($home)
        <div class="hero-slides">
            @foreach([
                ['src'=>'/assets/design/sd-ceria-nusantara-campus.png','alt'=>'Kawasan SD Ceria Nusantara dari udara','caption'=>'Kawasan SD Ceria Nusantara'],
                ['src'=>$data['image'],'alt'=>'Siswa belajar bersama di kelas','caption'=>'Belajar bersama','source'=>null],
                ['src'=>'/assets/design/asset-04.png','alt'=>'Suasana ruang kelas SD Ceria Nusantara','caption'=>'Ruang belajar yang ceria','source'=>null],
            ] as $slide)
            <img class="hero-slide @if($loop->first) is-active @endif" src="{{ $slide['src'] }}" alt="{{ $slide['alt'] }}" data-caption="{{ $slide['caption'] }}" data-concept="{{ $slide['concept'] ?? '' }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif width="1672" height="1045" @if(!$loop->first) aria-hidden="true" @endif>
            @endforeach
        </div>
        @else
        <img src="{{ $data['image'] }}" alt="{{ $contact ? 'Logo SD Ceria Nusantara' : 'Suasana belajar di SD Ceria Nusantara' }}" fetchpriority="high" width="1672" height="941">
        @endif
        @unless($contact)
            @if($home)
            <figcaption><span data-hero-caption>Kawasan SD Ceria Nusantara</span><button type="button" class="hero-playback" data-hero-toggle hidden>Jeda</button></figcaption>
            @else
            <figcaption>Belajar bersama</figcaption>
            @endif
        @endunless
    </figure>
</section>
