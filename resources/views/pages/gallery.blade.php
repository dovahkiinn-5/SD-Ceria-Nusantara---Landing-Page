@extends('layouts.site')
@section('title','Berita & Galeri — SD Ceria Nusantara')
@section('content')
<x-hero :data="$site['gallery']"/>
<section class="section"><div class="container"><x-heading eyebrow="CERITA SEKOLAH" title="Yang sedang berlangsung" subtitle="Kegiatan, karya, dan cerita terbaru dari keluarga sekolah."/>
    <div class="gallery-stories">
        @foreach($site['gallery']['news'] as $item)
        <details class="gallery-story">
            <summary><span class="gallery-story-category">{{ $item['category'] }}</span><h3>{{ $item['title'] }}</h3><span class="gallery-story-date">{{ $item['date'] }} · Cerita Sekolah</span><span class="gallery-story-more" aria-hidden="true">+</span></summary>
            <p>{{ $item['text'] }}</p>
        </details>
        @endforeach
    </div>
</div></section>
<section class="section tinted"><div class="container"><x-heading eyebrow="GALERI" title="Sekilas suasana sekolah" subtitle="Ruang belajar dan lingkungan yang mendampingi keseharian anak."/>
    <div class="gallery-grid">
        @foreach([
            ['image' => $site['home']['image'], 'title' => 'Belajar bersama', 'description' => 'Kegiatan di ruang kelas'],
            ['image' => $site['gallery']['image'], 'title' => 'Ruang belajar', 'description' => 'Tempat anak bereksplorasi'],
            ['image' => $site['home']['campus_image'] ?? '/assets/design/sd-ceria-nusantara-campus.png', 'title' => 'Lingkungan sekolah', 'description' => 'Area sekolah dan tempat bermain'],
        ] as $photo)
        <button class="gallery-photo" type="button" data-image="{{ \App\Support\PublicImage::url($photo['image']) }}" data-caption="{{ $photo['title'] }} — {{ $photo['description'] }}" aria-label="Perbesar foto {{ $photo['title'] }}">
            <img src="{{ \App\Support\PublicImage::url($photo['image']) }}" alt="" loading="lazy">
            <span class="gallery-photo-copy"><strong>{{ $photo['title'] }}</strong><small>{{ $photo['description'] }}</small></span>
            <span class="gallery-photo-icon" aria-hidden="true">↗</span>
        </button>
        @endforeach
    </div>
</div></section>
@endsection
