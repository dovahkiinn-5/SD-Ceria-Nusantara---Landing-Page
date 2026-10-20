@extends('layouts.site')
@php
    $labels = [1 => 'Data anak', 2 => 'Orang tua / wali', 3 => 'Unggah berkas', 4 => 'Periksa & kirim'];
    $heroTitles = [1 => 'Mari kenalan dengan si kecil!', 2 => 'Cerita sedikit tentang keluarga', 3 => 'Berkas siap, hati pun tenang', 4 => 'Tinggal satu langkah lagi!'];
    $descriptions = [1 => 'Isi informasi dasar calon siswa.', 2 => 'Kami akan menghubungi Anda melalui kontak ini.', 3 => 'Empat dokumen untuk membantu proses pemeriksaan.', 4 => 'Periksa data dan pilih cara pembayaran.'];
    $icons = [1 => '✦', 2 => '♡', 3 => '▣', 4 => '✓'];
    $documents = [
        'birth_certificate' => ['label' => 'Akta kelahiran', 'formats' => 'JPG, PNG, atau PDF', 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'family_card' => ['label' => 'Kartu Keluarga', 'formats' => 'JPG, PNG, atau PDF', 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'child_identity_card' => ['label' => 'Kartu Identitas Anak (KIA)', 'formats' => 'JPG, PNG, atau PDF', 'accept' => '.jpg,.jpeg,.png,.pdf'],
        'photo' => ['label' => 'Pas foto anak', 'formats' => 'JPG atau PNG', 'accept' => '.jpg,.jpeg,.png'],
    ];
    $reviewChild = ['child_name' => 'Nama lengkap', 'nickname' => 'Nama panggilan', 'birth_date' => 'Tanggal lahir', 'gender' => 'Jenis kelamin', 'previous_school' => 'Asal PAUD', 'support_needs' => 'Kebutuhan pendampingan'];
    $reviewParent = ['parent_name' => 'Nama orang tua / wali', 'relationship' => 'Hubungan', 'phone' => 'WhatsApp', 'email' => 'Email', 'province' => 'Provinsi', 'regency' => 'Kabupaten/kota', 'district' => 'Kecamatan', 'village' => 'Desa/kelurahan', 'address' => 'Alamat lengkap', 'postal_code' => 'Kode pos', 'emergency_contact' => 'Kontak darurat'];
    $selectedPayment = old('payment_method', $draft['payment']['method'] ?? '');
    $fee = (int) preg_replace('/[^0-9]/', '', (string) ($site['settings']['fee'] ?? '0'));
    $hideCta = true;
@endphp
@section('title', 'Langkah '.$step.' — '.$labels[$step].' | SD Ceria Nusantara')
@section('content')
<div class="registration-shell">
    <section class="registration-hero" aria-labelledby="registration-hero-title">
        <div class="registration-hero-copy">
            <p class="registration-eyebrow">PENERIMAAN PESERTA DIDIK BARU</p>
            <h1 id="registration-hero-title">{{ $heroTitles[$step] }}</h1>
            <p>Satu langkah demi satu langkah. Kami menemani prosesnya.</p>
        </div>
        <img class="registration-hero-art" src="/assets/design/registration-hero.svg" alt="" width="470" height="245" aria-hidden="true">
    </section>

    <div class="registration-layout">
        <aside class="registration-aside" aria-label="Bantuan pendaftaran">
            <div class="registration-aside-card">
                <p class="registration-aside-title">PROGRES PENDAFTARAN</p>
                <x-steps :step="$step"/>
            </div>
            <div class="registration-aside-help">
                <span aria-hidden="true">✦</span>
                <strong>Tenang, data tersimpan per langkah</strong>
                <p>Anda bisa kembali untuk memeriksa jawaban sebelum mengirim pendaftaran.</p>
                <a href="{{ route('contact') }}">Tanya panitia <span aria-hidden="true">↗</span></a>
            </div>
        </aside>

        <div class="registration-form-frame">
            <form class="registration-form" action="{{ $step === 4 ? route('registration.submit') : route('registration.save', $step) }}" method="post" enctype="multipart/form-data" @if($step === 4) data-single-submit @endif>
                @csrf
                <div class="registration-progress-label"><span>Langkah {{ $step }} dari 4</span><span>{{ $step === 4 ? 'Siap dikirim' : ($step * 25).'% terisi' }}</span></div>
                <div class="registration-progress" role="progressbar" aria-valuenow="{{ $step * 25 }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres pendaftaran"><span style="width:{{ $step * 25 }}%"></span></div>
                <header class="registration-form-heading">
                    <span class="registration-form-icon" aria-hidden="true">{{ $icons[$step] }}</span>
                    <div><p class="registration-form-kicker">FORMULIR PENDAFTARAN</p><h2>{{ $labels[$step] }}</h2><p>{{ $descriptions[$step] }}</p></div>
                </header>
                @include('partials.errors')

                @if($step === 1)
                    <div class="registration-groups">
                        <section class="registration-group-card" aria-labelledby="child-intro-title">
                            <h3 id="child-intro-title"><span class="registration-group-number">1</span> Kenalan dengan si kecil</h3>
                            <div class="registration-group-fields">
                                <div class="registration-field"><label for="child_name">Nama lengkap anak <b>*</b></label><input id="child_name" name="child_name" value="{{ old('child_name', $draft['child']['child_name'] ?? '') }}" placeholder="Sesuai akta kelahiran" required maxlength="120" autocomplete="name" @error('child_name') aria-invalid="true" @enderror>@error('child_name')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="nickname">Nama panggilan <b>*</b></label><input id="nickname" name="nickname" value="{{ old('nickname', $draft['child']['nickname'] ?? '') }}" placeholder="Contoh: Bima atau Naya" required maxlength="60" autocomplete="given-name" @error('nickname') aria-invalid="true" @enderror>@error('nickname')<small class="field-error">{{ $message }}</small>@enderror</div>
                            </div>
                        </section>
                        <section class="registration-group-card" aria-labelledby="child-info-title">
                            <h3 id="child-info-title"><span class="registration-group-number">2</span> Info calon siswa</h3>
                            <div class="registration-group-fields">
                                <div class="registration-field"><label for="birth_date">Tanggal lahir <b>*</b></label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $draft['child']['birth_date'] ?? '') }}" required max="{{ now()->subDay()->toDateString() }}" min="2005-01-02" @error('birth_date') aria-invalid="true" @enderror>@error('birth_date')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="gender">Jenis kelamin <b>*</b></label><select id="gender" name="gender" required @error('gender') aria-invalid="true" @enderror><option value="">Pilih salah satu</option>@foreach(['Laki-laki', 'Perempuan'] as $option)<option value="{{ $option }}" @selected(old('gender', $draft['child']['gender'] ?? '') === $option)>{{ $option }}</option>@endforeach</select>@error('gender')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="previous_school">Asal sekolah / PAUD <small>Opsional</small></label><input id="previous_school" name="previous_school" value="{{ old('previous_school', $draft['child']['previous_school'] ?? '') }}" placeholder="Nama sekolah sebelumnya" maxlength="180" @error('previous_school') aria-invalid="true" @enderror>@error('previous_school')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="support_needs">Kebutuhan pendampingan <small>Opsional</small></label><textarea id="support_needs" name="support_needs" rows="2" placeholder="Contoh: hal yang membantu anak nyaman belajar" maxlength="1000" @error('support_needs') aria-invalid="true" @enderror>{{ old('support_needs', $draft['child']['support_needs'] ?? '') }}</textarea>@error('support_needs')<small class="field-error">{{ $message }}</small>@enderror</div>
                            </div>
                        </section>
                    </div>
                    <p class="registration-note"><span aria-hidden="true">✦</span><span>Kolom bertanda <b>*</b> wajib diisi. Informasi tambahan membantu guru mengenal kebutuhan anak sejak awal.</span></p>
                @elseif($step === 2)
                    <div class="registration-groups">
                        <section class="registration-group-card" aria-labelledby="parent-intro-title">
                            <h3 id="parent-intro-title"><span class="registration-group-number">1</span> Orang tua atau wali</h3>
                            <div class="registration-group-fields">
                                <div class="registration-field"><label for="parent_name">Nama orang tua / wali <b>*</b></label><input id="parent_name" name="parent_name" value="{{ old('parent_name', $draft['parent']['parent_name'] ?? '') }}" placeholder="Nama lengkap" required maxlength="120" autocomplete="name" @error('parent_name') aria-invalid="true" @enderror>@error('parent_name')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="relationship">Hubungan dengan anak <b>*</b></label><select id="relationship" name="relationship" required @error('relationship') aria-invalid="true" @enderror><option value="">Pilih hubungan</option>@foreach(['Ayah', 'Ibu', 'Wali'] as $option)<option value="{{ $option }}" @selected(old('relationship', $draft['parent']['relationship'] ?? '') === $option)>{{ $option }}</option>@endforeach</select>@error('relationship')<small class="field-error">{{ $message }}</small>@enderror</div>
                            </div>
                        </section>
                        <section class="registration-group-card" aria-labelledby="parent-contact-title">
                            <h3 id="parent-contact-title"><span class="registration-group-number">2</span> Supaya mudah dihubungi</h3>
                            <div class="registration-group-fields">
                                <div class="registration-field"><label for="phone">Nomor WhatsApp <b>*</b></label><input id="phone" type="tel" inputmode="tel" name="phone" value="{{ old('phone', $draft['parent']['phone'] ?? '') }}" placeholder="Contoh: +6285770001234" required maxlength="25" autocomplete="tel" @error('phone') aria-invalid="true" @enderror><small class="field-hint">Nomor aktif untuk kabar pendaftaran.</small>@error('phone')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="email">Alamat email <b>*</b></label><input id="email" type="email" name="email" value="{{ old('email', $draft['parent']['email'] ?? '') }}" placeholder="nama@email.com" required maxlength="190" autocomplete="email" @error('email') aria-invalid="true" @enderror>@error('email')<small class="field-error">{{ $message }}</small>@enderror</div>
                            </div>
                        </section>
                        <section class="registration-group-card" aria-labelledby="parent-address-title">
                            <h3 id="parent-address-title"><span class="registration-group-number">3</span> Alamat tempat tinggal</h3>
                            <p class="registration-group-help">Isi sesuai alamat domisili anak pada dokumen keluarga agar panitia dapat memetakan wilayah pendaftar.</p>
                            <div class="registration-group-fields">
                                <div class="registration-field"><label for="province_code">Provinsi <b>*</b></label><select id="province_code" name="province_code" required data-province-select @error('province_code') aria-invalid="true" @enderror><option value="">Pilih provinsi</option>@foreach($regions as $code => $region)<option value="{{ $code }}" @selected(old('province_code', $draft['parent']['province_code'] ?? '') === (string) $code)>{{ $region['name'] }}</option>@endforeach</select>@error('province_code')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="regency_code">Kabupaten/kota <b>*</b></label><select id="regency_code" name="regency_code" required data-regency-select @error('regency_code') aria-invalid="true" @enderror><option value="">Pilih kabupaten/kota</option>@foreach($regions as $code => $region)<optgroup label="{{ $region['name'] }}" data-province="{{ $code }}">@foreach($region['regencies'] as $regencyCode => $name)<option value="{{ $regencyCode }}" @selected(old('regency_code', $draft['parent']['regency_code'] ?? '') === (string) $regencyCode)>{{ $name }}</option>@endforeach</optgroup>@endforeach</select>@error('regency_code')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="district">Kecamatan <b>*</b></label><input id="district" name="district" value="{{ old('district', $draft['parent']['district'] ?? '') }}" placeholder="Contoh: Gerunggang" required maxlength="120" autocomplete="address-level3" @error('district') aria-invalid="true" @enderror>@error('district')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="village">Desa/kelurahan <b>*</b></label><input id="village" name="village" value="{{ old('village', $draft['parent']['village'] ?? '') }}" placeholder="Contoh: Kacang Pedang" required maxlength="120" autocomplete="address-level4" @error('village') aria-invalid="true" @enderror>@error('village')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field registration-field-wide"><label for="address">Alamat lengkap <b>*</b></label><textarea id="address" name="address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, dan patokan bila perlu" required maxlength="1000" autocomplete="street-address" @error('address') aria-invalid="true" @enderror>{{ old('address', $draft['parent']['address'] ?? '') }}</textarea><small class="field-hint">Tulis alamat yang tercantum pada dokumen keluarga.</small>@error('address')<small class="field-error">{{ $message }}</small>@enderror</div>
                                <div class="registration-field"><label for="postal_code">Kode pos <small>Opsional</small></label><input id="postal_code" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" value="{{ old('postal_code', $draft['parent']['postal_code'] ?? '') }}" placeholder="Contoh: 33125" maxlength="5" autocomplete="postal-code" @error('postal_code') aria-invalid="true" @enderror>@error('postal_code')<small class="field-error">{{ $message }}</small>@enderror</div>
                            </div>
                        </section>
                        <section class="registration-group-card" aria-labelledby="parent-extra-title"><h3 id="parent-extra-title"><span class="registration-group-number">4</span> Kontak cadangan <small>Opsional</small></h3><div class="registration-group-fields"><div class="registration-field"><label for="emergency_contact">Nomor kontak darurat</label><input id="emergency_contact" type="tel" inputmode="tel" name="emergency_contact" value="{{ old('emergency_contact', $draft['parent']['emergency_contact'] ?? '') }}" placeholder="Nomor keluarga yang bisa dihubungi" maxlength="25" @error('emergency_contact') aria-invalid="true" @enderror>@error('emergency_contact')<small class="field-error">{{ $message }}</small>@enderror</div></div></section>
                    </div>
                @elseif($step === 3)
                    <p class="registration-note"><span aria-hidden="true">✦</span><span><strong>Siapkan 4 berkas.</strong> Masing-masing maksimal 1 MB. Foto atau scan harus jelas dan terbaca.</span></p>
                    <div class="registration-upload-grid">
                        @foreach($documents as $field => $document)
                            <div class="registration-upload-card @error($field) has-error @enderror">
                                <div class="registration-upload-heading"><label for="{{ $field }}">{{ $document['label'] }}</label><span>{{ isset($draft['documents'][$field]) ? 'Tersimpan' : 'Wajib' }}</span></div>
                                <div class="upload-dropzone" data-upload data-max-size="1048576" data-formats="{{ $document['formats'] }}">
                                    <span class="upload-prompt">＋ Pilih berkas <small>atau jatuhkan di sini</small></span>
                                    <input id="{{ $field }}" type="file" name="{{ $field }}" accept="{{ $document['accept'] }}" @required(!isset($draft['documents'][$field])) @error($field) aria-invalid="true" @enderror>
                                    <span class="upload-preview" data-upload-preview hidden><span class="upload-preview-visual"><img data-upload-preview-image alt="Pratinjau berkas terpilih" hidden><span data-upload-preview-document aria-hidden="true">PDF</span></span><span class="upload-preview-copy"><strong data-upload-preview-status>Siap diunggah</strong><small data-upload-preview-meta></small></span></span>
                                </div>
                                <small class="upload-formats">{{ $document['formats'] }} · Maksimal 1 MB</small>
                                @if(isset($draft['documents'][$field]))<small class="upload-current">✓ Tersimpan: {{ $draft['documents'][$field]['name'] }}. Pilih file baru untuk mengganti.</small>@endif
                                <small class="field-error" data-upload-error role="alert" @if(!$errors->has($field)) hidden @endif>@error($field){{ $message }}@enderror</small>
                            </div>
                        @endforeach
                    </div>
                    <p class="registration-upload-reminder">💡 Periksa nama, isi, dan keterbacaan berkas sebelum melanjutkan.</p>
                @else
                    <div class="registration-review-grid">
                        @foreach(['child' => ['Data anak', $reviewChild, 1], 'parent' => ['Orang tua / wali', $reviewParent, 2]] as $key => [$label, $fields, $editStep])
                            <section class="registration-review-card"><header><h3>{{ $label }}</h3><a href="{{ route('registration', $editStep) }}">Ubah <span aria-hidden="true">↗</span></a></header><dl>@foreach($fields as $field => $fieldLabel)@if(filled($draft[$key][$field] ?? null))<dt>{{ $fieldLabel }}</dt><dd>{{ $field === 'birth_date' ? \Carbon\Carbon::parse($draft[$key][$field])->translatedFormat('d F Y') : $draft[$key][$field] }}</dd>@endif @endforeach</dl></section>
                        @endforeach
                        <section class="registration-review-card registration-review-documents"><header><h3>Dokumen</h3><a href="{{ route('registration', 3) }}">Ubah <span aria-hidden="true">↗</span></a></header><ul>@foreach($documents as $field => $document)<li><span aria-hidden="true">✓</span> {{ $document['label'] }}</li>@endforeach</ul></section>
                    </div>
                    <section class="registration-payment" data-payment-choice aria-labelledby="payment-heading">
                        <header><h3 id="payment-heading">Pilih cara pembayaran</h3><strong>Rp{{ number_format($fee, 0, ',', '.') }}</strong></header>
                        <div class="registration-payment-options">
                            <label class="payment-option"><input type="radio" name="payment_method" value="offline" required @checked($selectedPayment === 'offline')><span><strong>Bayar di sekolah</strong><small>Langsung kepada petugas</small></span></label>
                            <label class="payment-option"><input type="radio" name="payment_method" value="transfer" required @checked($selectedPayment === 'transfer')><span><strong>Transfer bank BCA</strong><small>Unggah bukti pembayaran</small></span></label>
                        </div>
                        @error('payment_method')<small class="field-error">{{ $message }}</small>@enderror
                        <div class="payment-proof-panel" data-payment-proof-panel @if($selectedPayment !== 'transfer') hidden @endif>
                            <strong>Bukti pembayaran transfer</strong><p>Transfer Rp{{ number_format($fee, 0, ',', '.') }} ke BCA 8535459831 a.n. Muhamad Azmer Fahrezi. Unggah bukti maksimal 5 MB.</p>
                            @if(!empty($draft['payment']['proof']['name']))<small class="upload-current">✓ Bukti tersimpan: {{ $draft['payment']['proof']['name'] }}</small>@endif
                            <div class="upload-dropzone payment-upload @error('payment_proof') has-error @enderror" data-upload data-max-size="5242880" data-formats="PDF, JPG, PNG, atau WEBP">
                                <span class="upload-prompt">＋ Pilih bukti pembayaran <small>PDF, JPG, PNG, atau WEBP · Maksimal 5 MB</small></span>
                                <input id="payment_proof" type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png,.webp" @required($selectedPayment === 'transfer' && empty($draft['payment']['proof']['path'])) @error('payment_proof') aria-invalid="true" @enderror>
                                <span class="upload-preview" data-upload-preview hidden><span class="upload-preview-visual"><img data-upload-preview-image alt="Pratinjau bukti pembayaran" hidden><span data-upload-preview-document aria-hidden="true">PDF</span></span><span class="upload-preview-copy"><strong data-upload-preview-status>Siap diunggah</strong><small data-upload-preview-meta></small></span></span>
                            </div>
                            <small class="field-error" data-upload-error role="alert" @if(!$errors->has('payment_proof')) hidden @endif>@error('payment_proof'){{ $message }}@enderror</small>
                        </div>
                    </section>
                    <label class="registration-consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>Saya menyetujui <a href="{{ route('privacy') }}" target="_blank" rel="noopener">kebijakan privasi</a> dan pemrosesan data untuk pendaftaran sekolah.</span></label>
                    @error('consent')<small class="field-error">{{ $message }}</small>@enderror
                @endif

                <div class="registration-actions"><small>🔒 Data digunakan untuk proses pendaftaran sekolah.</small><div>@if($step > 1)<a class="registration-back" href="{{ route('registration', $step - 1) }}">← Kembali</a>@endif<button type="submit" class="registration-next">{{ $step === 4 ? 'Kirim pendaftaran' : 'Simpan & lanjutkan' }} <span aria-hidden="true">→</span></button></div></div>
            </form>
        </div>
    </div>
</div>
@if($step === 4)
@push('scripts')<script>
(() => {
    const section = document.querySelector('[data-payment-choice]');
    if (!section) return;
    const panel = section.querySelector('[data-payment-proof-panel]');
    const proof = section.querySelector('input[name="payment_proof"]');
    const hasSavedProof = @json(!empty($draft['payment']['proof']['path']));
    const update = () => {
        const transfer = section.querySelector('input[name="payment_method"]:checked')?.value === 'transfer';
        panel.hidden = !transfer;
        proof.disabled = !transfer;
        proof.required = transfer && !hasSavedProof;
    };
    section.querySelectorAll('input[name="payment_method"]').forEach(radio => radio.addEventListener('change', update));
    update();
})();
</script>@endpush
@endif
@endsection
