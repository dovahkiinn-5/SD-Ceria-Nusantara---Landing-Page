<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>@yield('title','Panel Admin') — SD Ceria Nusantara</title><link rel="icon" href="/assets/design/asset-02.png"><link rel="stylesheet" href="/css/site.css"><link rel="stylesheet" href="/css/admin.css"><script src="/js/site.js" defer></script></head>
<body class="admin-body">
@auth
<aside class="admin-sidebar"><a class="brand" href="{{ route('admin.dashboard') }}"><img src="/assets/design/asset-02.png" width="50" height="50" alt=""><span>SD CERIA<small>PANEL ADMIN</small></span></a><nav aria-label="Navigasi admin">
@foreach(['admin.dashboard'=>'Ringkasan','admin.records'=>'Pendaftar','visits'=>'Kunjungan','admin.content'=>'Konten Website','admin.profile'=>'Profil & Kata Sandi'] as $route=>$label)
@php($url = match($route){'admin.records'=>route('admin.records','applications'),'visits'=>route('admin.records','visits'),default=>route($route)})
<a href="{{ $url }}" @if(url()->current()===$url) aria-current="page" @endif>{{ $label }}</a>@endforeach
@if(auth()->user()->role==='owner')<a href="{{ route('admin.accounts') }}">Akun Admin</a>@endif
<a href="{{ route('home') }}" target="_blank" rel="noopener">Lihat Website ↗</a></nav>
<div class="admin-user"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role==='owner' ? 'Pemilik' : 'Editor' }}</small><form method="post" action="{{ route('admin.logout') }}">@csrf<button class="button secondary small">Keluar</button></form></div></aside>
@endauth
<main class="admin-main @guest login-main @endguest"><header class="admin-page-header"><p class="eyebrow">SD CERIA NUSANTARA</p><h1>@yield('title','Panel Admin')</h1>@yield('intro')</header>@include('partials.errors')@yield('content')</main>
</body></html>
