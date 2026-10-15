@extends('layouts.site')
@php
    $labels = [1 => 'Data Anak', 2 => 'Data Orang Tua', 3 => 'Unggah Berkas', 4 => 'Periksa & Kirim'];
    $descriptions = [
        1 => 'Kenalan dulu dengan calon bintang kecil yang akan belajar dan tumbuh bersama kami.',
        2 => 'Bagikan kontak orang tua atau wali agar tim sekolah mudah menghubungi.',
        3 => 'Unggah berkas yang jelas untuk membantu proses pemeriksaan pendaftaran.',
        4 => 'Periksa sekali lagi. Pastikan semua informasi sudah sesuai sebelum dikirim.',
    ];
    $stepIcons = [1 => '🎒', 2 => '💌', 3 => '📚', 4 => '🌟'];
    $hideCta = true;
@endphp
@section('title','Langkah '.$step.' — '.$labels[$step].' | SD Ceria Nusantara')
@section('content')
<section class="registration-banner">
    <div class="container registration-banner-inner">
        <div class="registration-banner-copy">
            <p class="registration-kicker">PENDAFTARAN SISWA BARU</p>
            <h1>Awal perjalanan<br><span>ceria si kecil!</span></h1>
            <p class="registration-lead">Isi data dengan santai. Kami pandu sampai selesai.</p>
        </div>
        <div class="registration-illustration">
            <span class="mascot-speech" aria-hidden="true">Hai, teman! 👋</span>
            <svg class="registration-mascot" viewBox="0 0 220 220" role="img" aria-label="Maskot anak sekolah tersenyum">
                <ellipse cx="112" cy="201" rx="64" ry="10" fill="#4b7890" opacity=".12"/>
                <circle cx="111" cy="106" r="91" fill="#fff0b9"/>
                <path d="M67 177c7-34 28-49 46-49s40 15 47 49v20H67z" fill="#1686a9" stroke="#14516c" stroke-width="4"/>
                <path d="M75 163c-18-7-26-20-26-36 0-8 5-13 11-12 6 1 8 9 9 16 3-9 8-15 14-13 8 2 5 13 0 22l13 16z" fill="#f5bd91" stroke="#704b3f" stroke-width="4" stroke-linejoin="round"/>
                <path d="M150 147c11-10 15-23 12-35-2-7 2-12 8-11 7 1 9 9 8 17-1 14-8 29-20 40z" fill="#f5bd91" stroke="#704b3f" stroke-width="4" stroke-linejoin="round"/>
                <path d="M103 128h20l16 22-25 17-27-17z" fill="#fff7df" stroke="#14516c" stroke-width="3" stroke-linejoin="round"/>
                <path d="M86 55c-1-23 13-38 34-38 19 0 32 14 31 37l-7 32H93z" fill="#503b35"/>
                <circle cx="77" cy="88" r="12" fill="#f5bd91" stroke="#704b3f" stroke-width="3"/>
                <circle cx="157" cy="88" r="12" fill="#f5bd91" stroke="#704b3f" stroke-width="3"/>
                <path d="M79 74c0-31 15-48 40-48 27 0 42 20 40 51l-7 36c-5 21-20 33-34 33-18 0-33-14-38-35z" fill="#f8c99e" stroke="#704b3f" stroke-width="4"/>
                <path d="M78 79c-7-28 8-59 39-62 27-3 43 17 43 43-12-2-22-10-28-21-9 13-27 21-54 22z" fill="#503b35"/>
                <path d="M90 92c5-5 12-5 17 0M124 92c5-5 12-5 17 0" fill="none" stroke="#704b3f" stroke-width="4" stroke-linecap="round"/>
                <ellipse cx="101" cy="102" rx="4" ry="6" fill="#362d2b"/>
                <ellipse cx="133" cy="102" rx="4" ry="6" fill="#362d2b"/>
                <circle cx="87" cy="113" r="7" fill="#f18c82" opacity=".55"/>
                <circle cx="148" cy="113" r="7" fill="#f18c82" opacity=".55"/>
                <path d="M108 117c7 9 17 9 24 0" fill="none" stroke="#a9514e" stroke-width="3.5" stroke-linecap="round"/>
                <path d="M63 179c8-17 23-26 36-29l16 16 16-16c15 4 28 14 35 29v18H63z" fill="#23a6bb" stroke="#14516c" stroke-width="4"/>
                <path d="M70 165l-17 9v30h29v-27z" fill="#f6c945" stroke="#d79a27" stroke-width="3"/>
                <path d="M53 176h29" stroke="#d79a27" stroke-width="3"/>
                <path d="M109 159h13" stroke="#14516c" stroke-width="3" stroke-linecap="round"/>
                <circle cx="168" cy="54" r="8" fill="#fff" opacity=".9"/>
                <path d="M178 69l4 5 6 1-5 4-1 6-4-5-6-1 5-4z" fill="#fff" opacity=".95"/>
            </svg>
            <span class="mascot-caption" aria-hidden="true">Teman baru di SD Ceria!</span>
        </div>
    </div>
</section>
<div class="container registration-container">
    <x-steps :step="$step"/>
    <div class="registration-form-card">
<form class="registration-form" action="{{ $step===4 ? route('registration.submit') : route('registration.save',$step) }}" method="post" enctype="multipart/form-data" @if($step===4) data-single-submit @endif>@csrf
<div class="registration-form-heading">
    <div>
        <p class="registration-form-kicker">LANGKAH {{ $step }} DARI 4</p>
        <h2>{{ $labels[$step] }}</h2>
        <p>{{ $descriptions[$step] }}</p>
    </div>
    <span class="registration-form-icon" aria-hidden="true">{{ $stepIcons[$step] }}</span>
</div>
@include('partials.errors')
@if($step===1)
<div class="registration-groups">
    <section class="registration-group-card">
        <header class="registration-group-heading">
            <span class="group-cartoon" aria-hidden="true">🧒🏻</span>
            <div><h3>Kenalan dengan si kecil</h3><p>Siapa nama calon murid kita?</p></div>
        </header>
        <div class="registration-group-fields">
            <label class="registration-field"><span class="field-label-row"><span>Nama lengkap anak <b>*</b></span></span><input name="child_name" value="{{ old('child_name',$draft['child']['child_name'] ?? '') }}" placeholder="Sesuai akta kelahiran" required maxlength="120" autocomplete="name"></label>
            <label class="registration-field"><span class="field-label-row"><span>Nama panggilan <b>*</b></span></span><input name="nickname" value="{{ old('nickname',$draft['child']['nickname'] ?? '') }}" placeholder="Contoh: Bima atau Naya" required maxlength="60" autocomplete="given-name"></label>
        </div>
    </section>
    <section class="registration-group-card">
        <header class="registration-group-heading">
            <span class="group-cartoon" aria-hidden="true">🎒</span>
            <div><h3>Info calon siswa</h3><p>Beberapa detail kecil tentang anak.</p></div>
        </header>
        <div class="registration-group-fields">
            <label class="registration-field"><span class="field-label-row"><span>Tanggal lahir <b>*</b></span></span><input type="date" name="birth_date" value="{{ old('birth_date',$draft['child']['birth_date'] ?? '') }}" required max="{{ now()->subDay()->toDateString() }}" min="2005-01-02"></label>
            <label class="registration-field"><span class="field-label-row"><span>Jenis kelamin <b>*</b></span></span><select name="gender" required><option value="">Pilih salah satu</option>@foreach(['Laki-laki','Perempuan'] as $option)<option @selected(old('gender',$draft['child']['gender'] ?? '')===$option)>{{ $option }}</option>@endforeach</select></label>
            <label class="registration-field"><span class="field-label-row"><span>Asal sekolah / PAUD <small>Opsional</small></span></span><input name="previous_school" value="{{ old('previous_school',$draft['child']['previous_school'] ?? '') }}" placeholder="Nama sekolah sebelumnya" maxlength="180"></label>
            <label class="registration-field"><span class="field-label-row"><span>Kebutuhan pendampingan <small>Opsional</small></span></span><input name="support_needs" value="{{ old('support_needs',$draft['child']['support_needs'] ?? '') }}" placeholder="Hal yang perlu kami tahu" maxlength="1000"></label>
        </div>
    </section>
</div>
@elseif($step===2)
<div class="registration-groups">
    <section class="registration-group-card">
        <header class="registration-group-heading">
            <span class="group-cartoon" aria-hidden="true">👨‍👩‍👧</span>
            <div><h3>Orang tua atau wali</h3><p>Kenalkan siapa yang mendampingi anak.</p></div>
        </header>
        <div class="registration-group-fields">
            <label class="registration-field"><span class="field-label-row"><span>Nama orang tua / wali <b>*</b></span></span><input name="parent_name" value="{{ old('parent_name',$draft['parent']['parent_name'] ?? '') }}" placeholder="Nama lengkap" required maxlength="120" autocomplete="name"></label>
            <label class="registration-field"><span class="field-label-row"><span>Hubungan dengan anak <b>*</b></span></span><select name="relationship" required><option value="">Pilih hubungan</option>@foreach(['Ayah','Ibu','Wali'] as $option)<option @selected(old('relationship',$draft['parent']['relationship'] ?? '')===$option)>{{ $option }}</option>@endforeach</select></label>
        </div>
    </section>
    <section class="registration-group-card">
        <header class="registration-group-heading">
            <span class="group-cartoon" aria-hidden="true">💌</span>
            <div><h3>Supaya mudah dihubungi</h3><p>Kontak ini dipakai untuk kabar pendaftaran.</p></div>
        </header>
        <div class="registration-group-fields">
            <label class="registration-field"><span class="field-label-row"><span>Nomor WhatsApp <b>*</b></span></span><input type="tel" name="phone" value="{{ old('phone',$draft['parent']['phone'] ?? '') }}" placeholder="Contoh: +6281234567890" required maxlength="25" autocomplete="tel"></label>
            <label class="registration-field"><span class="field-label-row"><span>Alamat email <b>*</b></span></span><input type="email" name="email" value="{{ old('email',$draft['parent']['email'] ?? '') }}" placeholder="nama@email.com" required maxlength="190" autocomplete="email"></label>
        </div>
    </section>
    <section class="registration-group-card">
        <header class="registration-group-heading">
            <span class="group-cartoon" aria-hidden="true">🏡</span>
            <div><h3>Info tambahan <small>Boleh dilewati</small></h3><p>Opsional, tapi bisa membantu kami.</p></div>
        </header>
        <div class="registration-group-fields">
            <label class="registration-field"><span class="field-label-row"><span>Alamat domisili</span></span><input name="address" value="{{ old('address',$draft['parent']['address'] ?? '') }}" placeholder="Jalan, nomor rumah, kelurahan..." maxlength="1000" autocomplete="street-address"></label>
            <label class="registration-field"><span class="field-label-row"><span>Kontak darurat</span></span><input type="tel" name="emergency_contact" value="{{ old('emergency_contact',$draft['parent']['emergency_contact'] ?? '') }}" placeholder="Nomor keluarga yang bisa dihubungi" maxlength="25"></label>
        </div>
    </section>
</div>
@elseif($step===3)
<div class="upload-intro"><span aria-hidden="true">✨</span><p>Siapkan tiga berkas ini, ya. Foto atau scan harus terbaca jelas dan ukuran masing-masing maksimal 1 MB.</p></div>
<div class="two-grid upload-grid">@foreach(['birth_certificate'=>'Akta kelahiran','family_card'=>'Kartu Keluarga','photo'=>'Pas foto anak'] as $field=>$label)
<label class="registration-field upload-field">
    <span class="field-label-row"><span>{{ $label }}</span><span class="field-badge required">Wajib</span></span>
    <span class="upload-dropzone" data-upload><span class="upload-icon" aria-hidden="true">{{ $field==='photo' ? '🖼️' : '📄' }}</span><span class="upload-prompt">Pilih berkas <small>atau jatuhkan di sini</small></span><input type="file" name="{{ $field }}" accept="{{ $field==='photo' ? '.jpg,.jpeg,.png' : '.jpg,.jpeg,.png,.pdf' }}" @required(!isset($draft['documents'][$field]))><span class="upload-formats">{{ $field==='photo' ? 'JPG atau PNG' : 'JPG, PNG, atau PDF' }} · Maks. 1 MB</span><span class="upload-selected" data-upload-name aria-live="polite">Belum memilih berkas</span></span>
    @if(isset($draft['documents'][$field]))<span class="upload-current"><span aria-hidden="true">✓</span> Sudah diunggah: {{ $draft['documents'][$field]['name'] }}. Pilih file baru untuk mengganti.</span>@endif
</label>
@endforeach</div>
<p class="upload-reminder"><span aria-hidden="true">💡</span> Pastikan nama dan isi dokumen terlihat jelas sebelum diunggah.</p>
@else
@foreach(['child'=>'Data anak','parent'=>'Orang tua / wali'] as $key=>$label)<section class="review-block"><h3>{{ $label }}</h3><a href="{{ route('registration',$key==='child' ? 1 : 2) }}">Ubah</a><dl>@foreach($draft[$key] as $field=>$value)<dt>{{ __('fields.'.$field) }}</dt><dd>{{ $value ?: '—' }}</dd>@endforeach</dl></section>@endforeach
<section class="review-block"><h3>Dokumen</h3><a href="{{ route('registration',3) }}">Ubah</a><p>3 berkas siap dikirim</p>@foreach($draft['documents'] as $file)<p>{{ $file['name'] }}</p>@endforeach</section>
<label class="checkbox-label"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>Saya menyetujui <a class="text-link" href="{{ route('privacy') }}" target="_blank" rel="noopener">kebijakan privasi</a> dan pemrosesan data untuk keperluan pendaftaran sekolah.</span></label>
@endif
<div class="form-actions"><small><span aria-hidden="true">🔒</span> Data pribadi dijaga aman. <span class="required-note">* Kolom wajib diisi.</span></small><div class="action-buttons">@if($step>1)<a class="button secondary" href="{{ route('registration',$step-1) }}"><span aria-hidden="true">←</span> Kembali</a>@endif<button class="button" type="submit">{{ $step===4 ? 'Kirim Pendaftaran 🎉' : 'Lanjutkan →' }}</button></div></div>
</form>
    </div>
</div>
@endsection
