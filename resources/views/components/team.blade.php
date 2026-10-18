@props(['site'])
@php($team = $site['teachers']['directory'])
<div class="card-grid team-grid">@foreach($site['teachers']['featured_people'] as $id) @php($member = $team[$id])<article class="team-card"><img src="{{ $member['photo'] ?: '/assets/design/teacher-1.png' }}" alt="{{ $member['name'] }}" width="512" height="250" loading="lazy"><h3>{{ $member['name'] }}</h3><p>{{ $member['role'] }}</p></article>@endforeach</div>
