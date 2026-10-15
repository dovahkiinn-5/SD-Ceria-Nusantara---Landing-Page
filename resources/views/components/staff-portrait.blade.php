@props(['index' => 0, 'name' => 'Profil anggota tim'])
<span {{ $attributes->class(['staff-portrait']) }} role="img" aria-label="Foto dummy {{ $name }}" style="--portrait-x: {{ ($index % 5) * 25 }}%; --portrait-y: {{ intdiv($index, 5) * 20 }}%"></span>
