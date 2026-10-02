@extends('layouts.site')
@section('title','Guru & Staf — SD Ceria Nusantara')
@section('content')
<x-hero :data="$site['teachers']"/>
<section class="section"><div class="container"><x-heading eyebrow="TIM KAMI" title="Hubungan hangat, bimbingan bermakna" subtitle="Pendidik yang hadir dengan perhatian dan semangat belajar."/><x-cards :items="$site['teachers']['values']"/></div></section>
<section class="section tinted"><div class="container"><x-heading eyebrow="KENALI TIM" title="Profil pendidik sekolah" subtitle="Kenali tim yang mendampingi perjalanan belajar anak."/><x-team :site="$site"/></div></section>
@endsection
