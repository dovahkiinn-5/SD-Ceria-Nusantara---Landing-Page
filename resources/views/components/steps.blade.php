@props(['step'=>1,'compact'=>false])
<ol {{ $attributes->class(['form-steps','compact'=>$compact]) }}>@foreach(['Data Anak','Data Orang Tua','Unggah Berkas','Periksa & Kirim'] as $label)<li class="{{ $loop->iteration <= $step ? 'active' : '' }}" @if($loop->iteration === $step) aria-current="step" @endif><span>{{ $loop->iteration }}</span><b>{{ $label }}</b></li>@endforeach</ol>
