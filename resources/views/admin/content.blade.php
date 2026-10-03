@extends('layouts.admin')
@php($sections=['settings'=>'Informasi Sekolah','home'=>'Beranda','about'=>'Tentang Kami','program'=>'Program','facilities'=>'Fasilitas','teachers'=>'Guru & Staf','gallery'=>'Berita & Galeri','contact'=>'Kontak'])
@section('title','Kelola Konten Website')
@section('intro')<p>Ubah isi website tanpa mengubah susunan desain.</p>@endsection
@section('content')<div class="content-tabs">@foreach($sections as $key=>$label)<a href="{{ route('admin.content',$key) }}" class="{{ $section===$key ? 'selected' : '' }}">{{ $label }}</a>@endforeach</div>
<form class="admin-panel content-editor" method="post" action="{{ route('admin.content.update',$section) }}" enctype="multipart/form-data">@csrf @method('PUT')<h2>{{ $sections[$section] }}</h2>
@foreach($fields as $key=>$value)
@php($imageField=(bool)preg_match('/(^image$|_image$|^logo$|\.photo$)/',$key))
@if(str_ends_with($key,'.icon'))<input type="hidden" name="values[{{ $key }}]" value="{{ $value }}">@else
<div class="content-field"><label for="content-{{ $loop->index }}">{{ \App\Services\ContentLabels::label($key) }}</label>
@if($imageField)<img class="editor-image" src="{{ $value }}" alt="Pratinjau gambar"><input type="hidden" name="values[{{ $key }}]" value="{{ $value }}"><input id="content-{{ $loop->index }}" type="file" name="uploads[{{ $loop->index }}]" accept=".jpg,.jpeg,.png,.webp"><small>JPG, PNG, atau WebP. Maksimal 1 MB. Biarkan kosong untuk mempertahankan gambar.</small>
@elseif(mb_strlen($value)>100 || str_contains($value,"\n") || in_array($key,['privacy','terms']))<textarea id="content-{{ $loop->index }}" name="values[{{ $key }}]" maxlength="10000">{{ old('values')[$key] ?? $value }}</textarea>
@else<input id="content-{{ $loop->index }}" name="values[{{ $key }}]" value="{{ old('values')[$key] ?? $value }}" maxlength="10000">@endif</div>
@endif
@endforeach<div class="editor-save"><button class="button blue">Simpan konten</button><a class="text-link" href="{{ route($section==='settings' ? 'home' : $section) }}" target="_blank" rel="noopener">Lihat halaman ↗</a></div></form>@endsection
