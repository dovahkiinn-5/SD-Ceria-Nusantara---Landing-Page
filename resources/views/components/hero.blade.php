@props(['data','home'=>false,'contact'=>false])
<section class="hero outer {{ $home ? 'hero-home' : '' }} {{ $contact ? 'hero-contact' : '' }}">
    <div class="hero-decoration" aria-hidden="true"></div>
    <div class="hero-copy"><p class="hero-eyebrow">{{ $data['eyebrow'] }}</p><h1>{{ $data['title'] }}</h1><p class="hero-description">@if(isset($data['tablet_description']))<span class="desktop-only">{{ $data['description'] }}</span><span class="tablet-only">{{ $data['tablet_description'] }}</span>@else{{ $data['description'] }}@endif</p>
    <div class="hero-actions"><a class="button" href="{{ route('registration',1) }}">Daftar Sekarang</a>@if($home)<a class="button white" href="{{ route('about') }}">Lihat Profil Sekolah</a>@endif</div></div>
    <figure class="hero-image"><img src="{{ $data['image'] }}" alt="{{ $contact ? 'Logo SD Ceria Nusantara' : 'Suasana belajar di SD Ceria Nusantara' }}" fetchpriority="high" width="1672" height="941">@unless($contact)<figcaption>Belajar bersama</figcaption>@endunless</figure>
</section>
