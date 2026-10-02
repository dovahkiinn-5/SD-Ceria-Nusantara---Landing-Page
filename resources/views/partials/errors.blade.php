@if($errors->any())<div class="errors" role="alert"><strong>Periksa kembali data berikut.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif
