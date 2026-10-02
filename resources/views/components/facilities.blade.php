@props(['site','labels'=>true])
<div class="facility-mosaic">
    <button class="image-zoom" type="button" data-image="{{ $site['settings']['facilities_image'] }}" data-caption="Fasilitas SD Ceria Nusantara" aria-label="Perbesar foto fasilitas"><img src="{{ $site['settings']['facilities_image'] }}" alt="Ruang kelas, perpustakaan, lapangan olahraga, ruang seni, lab komputer, dan UKS" width="1536" height="1024" loading="lazy"></button>
    @if($labels)<div class="facility-labels" aria-hidden="true">@foreach($site['facilities']['spaces'] as $space)<div><span>{{ $space }}</span></div>@endforeach</div>@endif
</div>
