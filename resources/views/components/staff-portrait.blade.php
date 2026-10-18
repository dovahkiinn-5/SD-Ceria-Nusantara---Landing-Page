@props(['index' => 0, 'name' => 'Profil anggota tim', 'photo' => null])
<<<<<<< HEAD
<span {{ $attributes->class(['staff-portrait']) }} role="img" aria-label="Foto profil {{ $name }}" @if($photo) style="background-image: url('{{ $photo }}'); background-size: cover; background-position: center;" @else style="--portrait-x: {{ ($index % 5) * 25 }}%; --portrait-y: {{ intdiv($index, 5) * 20 }}%" @endif></span>
=======
@php($safePhoto = is_string($photo) && preg_match('#^/(?:assets/(?:design|team)|media)/[a-zA-Z0-9_-]+\.(png|jpe?g|webp)$#', $photo) ? $photo : null)
<span {{ $attributes->class(['staff-portrait']) }} role="img" aria-label="Foto {{ $name }}" @if($safePhoto) style="background-image: url('{{ $safePhoto }}'); background-size: cover; background-position: center;" @else style="--portrait-x: {{ ($index % 5) * 25 }}%; --portrait-y: {{ intdiv($index, 5) * 20 }}%" @endif></span>
>>>>>>> e4cb613de806a237d4c0e8e3f83d86b3687b98c3
