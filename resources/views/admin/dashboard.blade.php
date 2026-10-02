@extends('layouts.admin')
@section('title','Ringkasan Sekolah')
@section('intro')<p>Kelola informasi sekolah dan dampingi setiap pendaftaran.</p>@endsection
@section('content')
<div class="admin-stats">@foreach($totals as $label=>$total)<article class="admin-panel"><p>{{ $label }}</p><strong>{{ number_format($total,0,',','.') }}</strong></article>@endforeach</div>
<section class="admin-panel"><div class="panel-heading"><h2>Pendaftar terbaru</h2><a class="text-link" href="{{ route('admin.records','applications') }}">Lihat semua →</a></div>@if(count($applications))<div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Nama anak</th><th>Status</th><th></th></tr></thead><tbody>@foreach($applications as $item)<tr><td>{{ $item['reference'] }}</td><td>{{ $item['child']['child_name'] }}</td><td><span class="status-badge">{{ ucfirst($item['status']) }}</span></td><td><a class="text-link" href="{{ route('admin.record',['applications',$item['id']]) }}">Detail</a></td></tr>@endforeach</tbody></table></div>@else<div class="empty-state"><x-icon name="users"/><h3>Belum ada pendaftar</h3><p>Pendaftaran yang dikirim melalui website akan muncul di sini.</p></div>@endif</section>
<div class="two-grid"><a class="admin-panel quick-link" href="{{ route('admin.content','home') }}"><x-icon name="book"/><h3>Perbarui landing page</h3><p>Ubah teks dan gambar sesuai informasi sekolah.</p></a><a class="admin-panel quick-link" href="{{ route('admin.records','visits') }}"><x-icon name="home"/><h3>Kelola kunjungan</h3><p>Tinjau permintaan dan konfirmasi jadwal keluarga.</p></a></div>
@endsection
