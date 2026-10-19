@extends('layouts.admin')
@section('title',$record['reference'] ?? 'Detail Kunjungan')
@section('content')<a class="text-link" href="{{ route('admin.records',$collection) }}">← Kembali ke daftar</a><div class="admin-detail-grid"><section class="admin-panel">
@if($collection==='applications')
@foreach(['child'=>'Data anak','parent'=>'Data orang tua / wali'] as $key=>$label)<h2>{{ $label }}</h2><dl class="detail-list">@foreach($record[$key] as $field=>$value)@continue(in_array($field, ['domicile_latitude','domicile_longitude','domicile_distance_km'], true))<dt>{{ __('fields.'.$field) }}</dt><dd>{{ $value ?: '—' }}</dd>@endforeach
@if($key==='parent' && isset($record['parent']['domicile_distance_km']))<dt>Jarak domisili ke sekolah</dt><dd>{{ number_format((float) $record['parent']['domicile_distance_km'], 2, ',', '.') }} km
@if(isset($record['parent']['domicile_latitude'], $record['parent']['domicile_longitude'])) · <a class="text-link" href="https://www.google.com/maps/search/?api=1&query={{ $record['parent']['domicile_latitude'] }},{{ $record['parent']['domicile_longitude'] }}" target="_blank" rel="noopener">Lihat titik domisili</a>@endif
</dd>@endif</dl>@endforeach
<h2>Berkas pendaftaran</h2><p>Dokumen terlampir pada pendaftaran {{ $record['reference'] }}. Unduh berkas untuk memeriksa data pendaftar.</p><div class="document-links">@foreach(['birth_certificate'=>'Akta kelahiran','family_card'=>'Kartu Keluarga','child_identity_card'=>'Kartu Identitas Anak (KIA)','photo'=>'Pas foto'] as $field=>$label)
@if(isset($record['documents'][$field]))
<div><a class="button secondary" href="{{ route('admin.document',[$record['id'],$field]) }}">↓ {{ $label }}</a>
<p>{{ $record['documents'][$field]['name'] }}
@if(isset($record['documents'][$field]['size']))
 · {{ number_format($record['documents'][$field]['size']/1024,1) }} KB
@endif
</p></div>
@else<p>{{ $label }}: Belum terlampir</p>@endif
@endforeach</div>
@php
$record['payment'] = array_replace($record['payment'] ?? [], ['method' => $record['payment_method'] ?? $record['payment']['method'] ?? '', 'proof' => $record['documents']['payment_proof'] ?? $record['payment']['proof'] ?? null]);
@endphp
@if(!empty($record['payment']['method']))<h2>Informasi pembayaran</h2><dl class="detail-list"><dt>Nominal</dt><dd>Rp{{ number_format($record['payment']['amount'] ?? 250000, 0, ',', '.') }}</dd><dt>Metode</dt><dd>{{ ($record['payment']['method'] ?? '') === 'transfer' ? 'Transfer BCA' : 'Bayar langsung di sekolah' }}</dd><dt>Status</dt><dd>{{ !empty($record['dashboard']['paid']) ? 'Terverifikasi oleh admin' : (($record['payment']['status'] ?? '') === 'menunggu_verifikasi' ? 'Menunggu verifikasi' : 'Belum dibayar') }}</dd>@if(($record['payment']['method'] ?? '') === 'transfer')<dt>Rekening tujuan</dt><dd>BCA 8535459831 · a.n. Muhamad Azmer Fahrezi</dd><dt>Bukti pembayaran</dt><dd>@if(!empty($record['payment']['proof']['path']))<a class="button secondary" href="{{ route('admin.document',[$record['id'],'payment_proof']) }}">↓ Unduh bukti transfer</a><br>{{ $record['payment']['proof']['name'] ?? 'Bukti transfer' }}@else Belum terlampir @endif</dd>@endif</dl>@endif
@else<h2>Data kunjungan</h2><dl class="detail-list">@foreach(['name'=>'Nama','phone'=>'WhatsApp','email'=>'Email','date'=>'Tanggal yang diminta','notes'=>'Catatan keluarga'] as $field=>$label)<dt>{{ $label }}</dt><dd>{{ $record[$field] ?: '—' }}</dd>@endforeach</dl>@endif
<p class="record-date">Dikirim: {{ \Carbon\Carbon::parse($record['created_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
</section><div><form class="admin-panel" method="post" action="{{ route('admin.record.update',[$collection,$record['id']]) }}">@csrf @method('PATCH')<h2>Peninjauan</h2><label>Status<select name="status">@foreach($collection==='applications' ? ['baru','ditinjau','perlu_perbaikan','diterima','ditolak'] : ['baru','dikonfirmasi','selesai','dibatalkan'] as $status)<option value="{{ $status }}" @selected($record['status']===$status)>{{ ucfirst($status) }}</option>@endforeach</select></label><label>Catatan admin<textarea name="admin_notes" maxlength="5000">{{ old('admin_notes',$record['admin_notes'] ?? '') }}</textarea></label><button class="button blue">Simpan perubahan</button></form>
@if(auth()->user()->role==='owner')<form class="admin-panel danger-panel" method="post" action="{{ route('admin.record.delete',[$collection,$record['id']]) }}" data-confirm="Hapus data ini beserta semua berkasnya? Tindakan ini tidak dapat dibatalkan.">@csrf @method('DELETE')<h3>Hapus data</h3><p>Data dan berkas yang dihapus tidak dapat dikembalikan.</p><button class="button danger">Hapus permanen</button></form>@endif</div></div>@endsection
