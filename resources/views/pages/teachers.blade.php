@extends('layouts.site')
@section('title','Guru & Staf — SD Ceria Nusantara')
@section('content')
@php($directory = require resource_path('content/staff-directory.php'))
@php($people = $directory['people'])
<section class="people-hero outer">
    <div><p class="eyebrow">KENALI KELUARGA SEKOLAH KAMI</p><h1>Wajah ramah,<br>teman tumbuh anak.</h1><p>Di kelas, di perpustakaan, hingga di gerbang sekolah—ada tim yang siap mendampingi langkah kecil buah hati Anda.</p><div class="people-hero-actions"><a class="button" href="#direktori">Berkenalan dengan tim ↗</a><a class="text-link" href="#struktur">Lihat struktur sekolah ↓</a></div><div class="people-facts"><span><strong>6</strong> jenjang kelas</span><span><strong>30</strong> profil contoh</span><span><strong>1</strong> semangat mendampingi</span></div></div>
    <div class="people-hero-photo"><x-staff-portrait :index="$people[0]['portrait']" :name="$people[0]['name']"/><div class="people-photo-note"><span aria-hidden="true">♡</span><div><strong>Setiap anak dikenal,</strong><br>setiap keluarga didengar.</div></div><span class="people-photo-badge">Bertumbuh bersama</span></div>
</section>
<div class="container people-demo-note"><span aria-hidden="true">ⓘ</span><p><strong>Contoh profil sekolah.</strong> Seluruh nama, foto, jabatan, dan pembagian tugas di halaman ini adalah data dummy. Susunan guru, layanan, dan mata pelajaran sebenarnya perlu dikonfirmasi kepada sekolah.</p></div>
<section class="section people-values"><div class="container"><x-heading eyebrow="LEBIH DARI MENGAJAR" title="Mendampingi dengan hati" subtitle="Kami ingin anak merasa aman, orang tua merasa tenang, dan komunikasi selalu terbuka."/><x-cards :items="$site['teachers']['values']"/></div></section>
<section class="section tinted" id="direktori"><div class="container">
    <x-heading eyebrow="ORANG-ORANG DI BALIK CERITA ANAK" title="Mari berkenalan dengan tim" subtitle="Kenali peran setiap orang, agar Anda tahu siapa yang dapat membantu."/>
    <div class="people-tools"><div class="people-filters" role="group" aria-label="Filter tim sekolah"><button type="button" class="is-active" data-team-filter="semua" aria-pressed="true">Semua tim <span>30</span></button><button type="button" data-team-filter="pimpinan" aria-pressed="false">Pimpinan <span>3</span></button><button type="button" data-team-filter="guru" aria-pressed="false">Guru <span>16</span></button><button type="button" data-team-filter="staf" aria-pressed="false">Staf pendukung <span>11</span></button></div><label class="people-search"><span>Cari nama atau peran</span><input type="search" data-team-search placeholder="Misalnya: wali kelas, UKS…" autocomplete="off"></label></div>
    <p class="people-results" data-team-count aria-live="polite">Menampilkan 30 anggota tim</p>
    <div class="people-grid">
    @foreach($people as $person)
        <article class="person-card" data-team-group="{{ $person['group'] }}" data-team-text="{{ mb_strtolower($person['name'].' '.$person['role'].' '.$person['description']) }}"><div class="person-photo"><x-staff-portrait :index="$person['portrait']" :name="$person['name']"/><span class="person-category">{{ ['pimpinan'=>'Pimpinan sekolah','guru'=>'Tim pengajar','staf'=>'Sahabat sekolah'][$person['group']] }}</span></div><div class="person-copy"><p class="person-role">{{ $person['role'] }}</p><h3>{{ $person['name'] }}</h3><p>{{ $person['description'] }}</p></div></article>
    @endforeach
    </div><p class="people-empty" data-team-empty hidden>Belum ada profil yang cocok. Coba nama atau peran lain.</p>
</div></section>
<section class="section people-subjects" id="mata-pelajaran"><div class="container"><x-heading eyebrow="BELAJAR UTUH, BERTUMBUH BERSAMA" title="Siapa yang mendampingi pelajaran anak?" subtitle="Wali kelas mendampingi pelajaran inti, bekerja sama dengan guru bidang studi."/><div class="subject-grid">
@foreach($directory['subjects'] as $subject)
<article class="subject-card"><span class="subject-number" aria-hidden="true">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><h3>{{ $subject['title'] }}</h3><p>{{ $subject['text'] }}</p><div>{{ $subject['by'] }}</div></article>
@endforeach
</div><p class="people-subject-note">Pembagian pelajaran menyesuaikan jenjang, kurikulum, dan program sekolah. Muatan lokal, pilihan seni, serta kegiatan pengayaan dapat berbeda pada setiap kelas.</p></div></section>
<section class="section tinted people-organization" id="struktur"><div class="container"><x-heading eyebrow="SALING TERHUBUNG, SALING MENDUKUNG" title="Struktur keluarga sekolah" subtitle="Satu tim yang bekerja bersama untuk pengalaman sekolah yang aman dan menyenangkan."/>
<div class="organization-tree" aria-label="Struktur kepegawaian sekolah">
<div class="organization-head"><x-staff-portrait :index="$people[0]['portrait']" :name="$people[0]['name']"/><p>Kepala Sekolah</p><h3>{{ $people[0]['name'] }}</h3><span>Memimpin dan mengoordinasikan seluruh tim</span></div>
<div class="organization-branches">
<section class="organization-branch"><div class="organization-lead"><p>Bidang pembelajaran</p><h3>Wakil Kepala • Kurikulum</h3><span>{{ $people[1]['name'] }}</span></div><div class="organization-members"><h4>Wali kelas & guru mata pelajaran</h4><p>Wali kelas 1–6, guru agama, Bahasa Inggris, PJOK, seni, dan muatan lokal.</p><h4>Dukungan pembelajaran</h4><p>Pustakawan serta teknisi IT & sarana prasarana berkoordinasi mendukung kegiatan belajar.</p></div></section>
<section class="organization-branch"><div class="organization-lead"><p>Bidang pendampingan anak</p><h3>Wakil Kepala • Kesiswaan</h3><span>{{ $people[2]['name'] }}</span></div><div class="organization-members"><h4>Kesejahteraan & tumbuh kembang</h4><p>Konselor, pendamping belajar inklusif, dan petugas UKS.</p><h4>Kegiatan & komunikasi keluarga</h4><p>Berkoordinasi dengan wali kelas untuk pembiasaan, kegiatan siswa, dan kebutuhan anak.</p></div></section>
<section class="organization-branch"><div class="organization-lead"><p>Bidang layanan sekolah</p><h3>Tata Usaha & Operasional</h3><span>{{ $people[21]['name'] }}</span></div><div class="organization-members"><h4>Administrasi & data</h4><p>Tata usaha dan penerimaan siswa, bendahara, serta operator sekolah.</p><h4>Lingkungan aman & nyaman</h4><p>Petugas keamanan, kebersihan, dan pengelola kantin.</p></div></section>
</div></div><p class="people-subject-note">Bagan contoh untuk memperjelas koordinasi internal. Seluruh bidang berada di bawah arahan kepala sekolah dan bekerja sama lintas tim.</p>
</div></section>
<section class="section"><div class="container people-help"><div><p class="eyebrow">KAMI SIAP MENDENGARKAN</p><h2>Butuh berbicara dengan tim yang tepat?</h2><p>Mulai dari pertanyaan belajar anak, administrasi, hingga rencana kunjungan. Tim tata usaha akan membantu menghubungkan Anda.</p></div><a class="button" href="{{ route('contact') }}">Hubungi sekolah ↗</a></div></section>
@endsection
@push('scripts')
<script src="/js/team-directory.js" defer></script>
@endpush
