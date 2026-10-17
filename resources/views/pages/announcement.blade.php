@extends('layouts.site')
@php($hideCta = true)
@section('title','Pengumuman Hasil Seleksi — SD Ceria Nusantara')
@section('content')
<section class="announcement-hero">
    <span class="announcement-orb announcement-orb-one" aria-hidden="true"></span>
    <span class="announcement-orb announcement-orb-two" aria-hidden="true"></span>
    <div class="container announcement-hero-inner">
        <div class="announcement-hero-copy">
            <p class="announcement-kicker">PENGUMUMAN PESERTA DIDIK BARU</p>
            <h1>Cek hasil seleksi<br><span>dengan mudah</span></h1>
            <p>Masukkan nomor pendaftaran dan tanggal lahir anak untuk melihat hasil terbaru dari panitia.</p>
        </div>
        <div class="announcement-hero-art" aria-hidden="true">
            <span class="announcement-art-star">★</span>
            <span class="announcement-art-card"><b>Hasil seleksi</b><i></i><i></i><em>✓</em></span>
        </div>
    </div>
</section>

<section class="announcement-shell">
    <div class="announcement-lookup-card">
        <div class="announcement-card-heading">
            <span class="announcement-heading-icon" aria-hidden="true">🔎</span>
            <div><p>CEK STATUS</p><h2>Temukan pengumumanmu</h2><span>Pastikan data sama seperti saat melakukan pendaftaran.</span></div>
        </div>

        @include('partials.errors')

        <form method="post" action="{{ route('announcement.lookup') }}" class="announcement-form">
            @csrf
            <div class="announcement-form-grid">
                <label for="announcement-reference">
                    <span>Nomor pendaftaran</span>
                    <input id="announcement-reference" name="reference" value="{{ old('reference') }}" placeholder="Contoh: CN-2026-XXXXXXXXXX" maxlength="40" autocapitalize="characters" spellcheck="false" required>
                    <small>Nomor yang diterima setelah pendaftaran dikirim.</small>
                </label>
                <label for="announcement-birth-date">
                    <span>Tanggal lahir anak</span>
                    <input id="announcement-birth-date" type="date" name="birth_date" max="{{ now()->subDay()->toDateString() }}" required autocomplete="bday">
                    <small>Digunakan untuk memastikan hasil hanya dilihat keluarga.</small>
                </label>
            </div>
            <div class="announcement-submit-row">
                <p><span aria-hidden="true">🔒</span> Data ini hanya digunakan untuk memeriksa hasil seleksi.</p>
                <button class="button announcement-submit" type="submit">Periksa Hasil</button>
            </div>
        </form>
    </div>

    @if($result)
    <article class="announcement-result announcement-result-{{ $result['tone'] }}" role="status" aria-live="polite">
        <div class="announcement-result-icon" aria-hidden="true">
            @if($result['status']==='diterima')✓@elseif($result['status']==='ditolak')i@else⌛@endif
        </div>
        <div class="announcement-result-copy">
            <p class="announcement-result-label">{{ $result['label'] }}</p>
            <h2>{{ $result['title'] }}</h2>
            <p>{{ $result['message'] }}</p>
            <dl>
                <div><dt>Nama calon siswa</dt><dd>{{ $result['child_name'] }}</dd></div>
                <div><dt>Nomor pendaftaran</dt><dd>{{ $result['reference'] }}</dd></div>
            </dl>
            @if($result['status']==='diterima' && $whatsappGroupUrl)
            <div class="announcement-whatsapp">
                <div><strong>Langkah berikutnya</strong><span>Gabung ke grup resmi orang tua untuk menerima informasi kegiatan selanjutnya.</span></div>
                <a class="button" href="{{ $whatsappGroupUrl }}" target="_blank" rel="noopener noreferrer">Gabung Grup WhatsApp</a>
            </div>
            @elseif($result['status']==='diproses')
            <p class="announcement-result-note">Silakan periksa kembali halaman ini secara berkala.</p>
            @endif
        </div>
    </article>
    @endif

    <div class="announcement-help">
        <span aria-hidden="true">💬</span>
        <div><strong>Nomor pendaftaran tidak ditemukan?</strong><p>Periksa kembali penulisan nomor atau hubungi panitia sekolah untuk bantuan.</p></div>
        <a class="text-link" href="https://wa.me/{{ preg_replace('/\D/','',$site['settings']['whatsapp']) }}" target="_blank" rel="noopener">Hubungi panitia</a>
    </div>
</section>
@endsection
