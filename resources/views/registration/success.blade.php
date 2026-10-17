@extends('layouts.site')
@section('title','Pendaftaran berhasil — SD Ceria Nusantara')
@section('content')
<section class="success-header"><div class="container"><div class="success-check" aria-hidden="true">✓</div><h1>Pendaftaran berhasil dikirim</h1><p>Terima kasih. Tim penerimaan akan segera menghubungi Anda.</p></div></section>
<section class="success-content"><div class="registration-number"><small>NOMOR PENDAFTARAN</small><strong>{{ $reference }}</strong><p>Simpan nomor ini bersama tanggal lahir anak untuk memantau hasil seleksi.</p></div>
<x-cards :items="[['title'=>'Simpan nomor pendaftaran','text'=>'Nomor ini diperlukan untuk memeriksa pengumuman.','icon'=>'book'],['title'=>'Pantau pengumuman','text'=>'Periksa hasil seleksi melalui halaman Pengumuman.','icon'=>'mail'],['title'=>'Tunggu informasi lanjutan','text'=>'Panitia akan memperbarui hasil setelah proses pemeriksaan selesai.','icon'=>'phone']]"/>
<x-heading eyebrow="LANGKAH BERIKUTNYA" title="Pantau hasil pendaftaran" subtitle="Gunakan nomor pendaftaran dan tanggal lahir anak untuk melihat hasil seleksi."/><div class="form-actions"><a class="button blue" href="{{ route('announcement') }}">Cek Pengumuman</a><a class="text-link" href="https://wa.me/{{ preg_replace('/\D/','',$site['settings']['whatsapp']) }}" target="_blank" rel="noopener">Hubungi panitia</a><a class="text-link" href="{{ route('home') }}">Kembali ke Beranda</a></div></section>
@endsection
