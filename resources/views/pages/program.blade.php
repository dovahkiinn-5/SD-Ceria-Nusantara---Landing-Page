@extends('layouts.site')
@section('title','Program — SD Ceria Nusantara')
@section('content')
<x-hero :data="$site['program']"/>
<section class="section" id="pendekatan"><div class="container"><x-heading eyebrow="PENDEKATAN" title="Belajar aktif, tumbuh utuh" subtitle="Menyeimbangkan akademik, karakter, dan kreativitas anak."/><x-cards :items="$site['program']['approach']"/></div></section>
<section class="section tinted"><div class="container"><x-heading eyebrow="KURIKULUM" title="Terhubung dengan kehidupan anak" subtitle="Kurikulum terpadu dengan proyek, eksplorasi, dan refleksi."/><div class="card-grid">@foreach($site['program']['curriculum'] as $item)<article class="info-card"><h3>{{ $item['title'] }}</h3><ul>@foreach(explode("\n",$item['text']) as $line)<li>{{ $line }}</li>@endforeach</ul></article>@endforeach</div></div></section>
<section class="section"><div class="container"><x-heading eyebrow="KEGIATAN" title="Jelajahi minat di luar kelas" subtitle="Kegiatan setelah kelas untuk menumbuhkan minat dan bakat."/><x-cards :items="$site['program']['activities']"/></div></section>
@endsection
