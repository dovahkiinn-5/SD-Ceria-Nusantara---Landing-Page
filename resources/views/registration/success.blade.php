@extends('layouts.site')
@section('title','Pendaftaran berhasil — SD Ceria Nusantara')
@section('content')
<section class="success-header"><div class="container"><div class="success-check" aria-hidden="true">✓</div><h1>Pendaftaran berhasil dikirim</h1><p>Terima kasih. Tim penerimaan akan segera menghubungi Anda.</p></div></section>
<section class="success-content"><div class="registration-number"><small>NOMOR PENDAFTARAN</small><strong>{{ $reference }}</strong><p>Simpan nomor ini untuk memantau proses pendaftaran.</p></div>
<x-cards :items="[['title'=>'Simpan nomor pendaftaran','text'=>'Gunakan nomor ini saat menghubungi panitia.','icon'=>'book'],['title'=>'Pantau email dan WhatsApp','text'=>'Konfirmasi akan dikirim ke kontak yang didaftarkan.','icon'=>'mail'],['title'=>'Tunggu informasi lanjutan','text'=>'Panitia akan memberi hasil melalui WhatsApp.','icon'=>'phone']]"/>
<x-heading eyebrow="BUTUH BANTUAN?" title="Tim sekolah siap membantu" subtitle="Hubungi panitia untuk bantuan pendaftaran."/><div class="form-actions"><a class="button blue" href="https://wa.me/{{ preg_replace('/\D/','',$site['settings']['whatsapp']) }}" target="_blank" rel="noopener">WhatsApp {{ $site['settings']['whatsapp'] }}</a><a class="text-link" href="{{ route('home') }}">Kembali ke Beranda</a></div></section>
@endsection
