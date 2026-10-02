@extends('layouts.site')
@section('title','Fasilitas — SD Ceria Nusantara')
@section('content')
<x-hero :data="$site['facilities']"/>
<section class="section"><div class="container"><x-heading eyebrow="JELAJAHI RUANG" title="Fasilitas untuk berbagai cara belajar" subtitle="Ruang yang mendukung rasa ingin tahu dan kreativitas anak."/><x-facilities :site="$site"/></div></section>
<section class="section"><div class="container"><x-heading eyebrow="PENGALAMAN ANAK" title="Nyaman untuk eksplorasi sehari-hari"/><x-cards :items="$site['facilities']['features']"/><p class="image-caption">Kunjungi sekolah dan temukan ruang belajar favorit anak.</p></div></section>
@endsection
