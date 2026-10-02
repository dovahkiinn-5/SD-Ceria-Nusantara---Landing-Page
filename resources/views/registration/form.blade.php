@extends('layouts.site')
@php($labels=[1=>'Data Anak',2=>'Data Orang Tua',3=>'Unggah Berkas',4=>'Periksa & Kirim'])
@section('title','Langkah '.$step.' — '.$labels[$step].' | SD Ceria Nusantara')
@section('content')
<section class="registration-banner"><div class="container"><p class="eyebrow">PENDAFTARAN ONLINE</p><h1>Langkah {{ $step }} — {{ $labels[$step] }}</h1><p>Lengkapi data dengan benar untuk memulai perjalanan belajar anak.</p></div></section>
<div class="container registration-container"><x-steps :step="$step"/>
<form class="registration-form" action="{{ $step===4 ? route('registration.submit') : route('registration.save',$step) }}" method="post" enctype="multipart/form-data" @if($step===4) data-single-submit @endif>@csrf<h2>{{ $labels[$step] }}</h2>@include('partials.errors')
@if($step===1)
<div class="two-grid">
<label>Nama lengkap anak *<input name="child_name" value="{{ old('child_name',$draft['child']['child_name'] ?? '') }}" placeholder="Sesuai akta kelahiran" required maxlength="120" autocomplete="name"></label>
<label>Nama panggilan *<input name="nickname" value="{{ old('nickname',$draft['child']['nickname'] ?? '') }}" placeholder="Nama yang biasa digunakan" required maxlength="60"></label>
<label>Tanggal lahir *<input type="date" name="birth_date" value="{{ old('birth_date',$draft['child']['birth_date'] ?? '') }}" required max="{{ now()->subDay()->toDateString() }}" min="2005-01-02"></label>
<label>Jenis kelamin *<select name="gender" required><option value="">Pilih salah satu</option>@foreach(['Laki-laki','Perempuan'] as $option)<option @selected(old('gender',$draft['child']['gender'] ?? '')===$option)>{{ $option }}</option>@endforeach</select></label>
<label>Asal sekolah / PAUD<input name="previous_school" value="{{ old('previous_school',$draft['child']['previous_school'] ?? '') }}" placeholder="Opsional" maxlength="180"></label>
<label>Kebutuhan pendampingan<input name="support_needs" value="{{ old('support_needs',$draft['child']['support_needs'] ?? '') }}" placeholder="Opsional" maxlength="1000"></label>
</div>
@elseif($step===2)
<div class="two-grid">
<label>Nama orang tua / wali *<input name="parent_name" value="{{ old('parent_name',$draft['parent']['parent_name'] ?? '') }}" placeholder="Nama lengkap" required maxlength="120" autocomplete="name"></label>
<label>Hubungan dengan anak *<select name="relationship" required><option value="">Pilih hubungan</option>@foreach(['Ayah','Ibu','Wali'] as $option)<option @selected(old('relationship',$draft['parent']['relationship'] ?? '')===$option)>{{ $option }}</option>@endforeach</select></label>
<label>Nomor WhatsApp aktif *<input type="tel" name="phone" value="{{ old('phone',$draft['parent']['phone'] ?? '') }}" placeholder="Contoh: +6281234567890" required maxlength="25" autocomplete="tel"></label>
<label>Alamat email *<input type="email" name="email" value="{{ old('email',$draft['parent']['email'] ?? '') }}" placeholder="contoh@email.com" required maxlength="190" autocomplete="email"></label>
<label>Alamat domisili<input name="address" value="{{ old('address',$draft['parent']['address'] ?? '') }}" placeholder="Alamat lengkap" maxlength="1000" autocomplete="street-address"></label>
<label>Kontak darurat<input type="tel" name="emergency_contact" value="{{ old('emergency_contact',$draft['parent']['emergency_contact'] ?? '') }}" placeholder="Nomor yang dapat dihubungi" maxlength="25"></label>
</div>
@elseif($step===3)
<div class="two-grid">@foreach(['birth_certificate'=>'Akta kelahiran','family_card'=>'Kartu Keluarga','photo'=>'Pas foto anak'] as $field=>$label)<label>{{ $label }} *<input type="file" name="{{ $field }}" accept="{{ $field==='photo' ? '.jpg,.jpeg,.png' : '.jpg,.jpeg,.png,.pdf' }}" @required(!isset($draft['documents'][$field]))><span class="upload-help">{{ $field==='photo' ? 'JPG, PNG' : 'JPG, PNG, PDF' }} • maksimal 5 MB</span>@if(isset($draft['documents'][$field]))<span class="upload-help">Sudah diunggah: {{ $draft['documents'][$field]['name'] }}. Pilih berkas untuk mengganti.</span>@endif</label>@endforeach</div>
<p class="upload-help">Periksa kejelasan dokumen sebelum melanjutkan.</p>
@else
@foreach(['child'=>'Data anak','parent'=>'Orang tua / wali'] as $key=>$label)<section class="review-block"><h3>{{ $label }}</h3><a href="{{ route('registration',$key==='child' ? 1 : 2) }}">Ubah</a><dl>@foreach($draft[$key] as $field=>$value)<dt>{{ __('fields.'.$field) }}</dt><dd>{{ $value ?: '—' }}</dd>@endforeach</dl></section>@endforeach
<section class="review-block"><h3>Dokumen</h3><a href="{{ route('registration',3) }}">Ubah</a><p>3 berkas siap dikirim</p>@foreach($draft['documents'] as $file)<p>{{ $file['name'] }}</p>@endforeach</section>
<label class="checkbox-label"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>Saya menyetujui <a class="text-link" href="{{ route('privacy') }}" target="_blank" rel="noopener">kebijakan privasi</a> dan pemrosesan data untuk keperluan pendaftaran sekolah.</span></label>
@endif
<div class="form-actions"><small>* Wajib diisi. Data disimpan hanya setelah formulir dikirim.</small><div class="action-buttons">@if($step>1)<a class="button secondary" href="{{ route('registration',$step-1) }}">Kembali</a>@endif<button class="button" type="submit">{{ $step===4 ? 'Kirim Formulir' : 'Lanjutkan' }}</button></div></div>
</form></div>
@endsection
