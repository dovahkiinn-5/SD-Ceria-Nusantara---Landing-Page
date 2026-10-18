@props(['site'])
<<<<<<< HEAD
@php($team = $site['teachers']['directory'])
<div class="card-grid team-grid">@foreach($site['teachers']['featured_people'] as $id) @php($member = $team[$id])<article class="team-card"><img src="{{ $member['photo'] ?: '/assets/design/teacher-1.png' }}" alt="{{ $member['name'] }}" width="512" height="250" loading="lazy"><h3>{{ $member['name'] }}</h3><p>{{ $member['role'] }}</p></article>@endforeach</div>
=======
<div class="card-grid team-grid">@foreach(\App\Services\TeacherDirectory::homepagePeople($site['teachers']) as $member)<article class="team-card"><x-person-photo :person="$member"/><h3>{{ $member['name'] }}</h3><p>{{ $member['role'] }}</p></article>@endforeach</div>
>>>>>>> e4cb613de806a237d4c0e8e3f83d86b3687b98c3
