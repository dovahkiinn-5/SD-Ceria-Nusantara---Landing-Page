<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeacherDirectory
{
    private const IDS = [
        'kepala-sekolah', 'wakil-kurikulum', 'wakil-kesiswaan',
        'wali-kelas-1', 'wali-kelas-2', 'wali-kelas-3', 'wali-kelas-4', 'wali-kelas-5', 'wali-kelas-6',
        'guru-agama-islam', 'guru-agama-kristen', 'guru-agama-katolik', 'guru-agama-hindu',
        'guru-agama-buddha', 'guru-agama-khonghucu', 'guru-bahasa-inggris', 'guru-pjok',
        'guru-seni-budaya', 'guru-muatan-lokal', 'konselor', 'pendamping-inklusif',
        'tata-usaha', 'bendahara', 'operator', 'pustakawan', 'petugas-uks', 'teknisi',
        'keamanan', 'kebersihan', 'kantin',
    ];

    public static function defaults(): array
    {
        return (require resource_path('content/staff-directory.php')) + [
            'featured_people' => [0, 1, 2, 3, 16, 21],
            'directory_eyebrow' => 'KENALI KELUARGA SEKOLAH KAMI',
            'directory_title' => "Wajah ramah,\nteman tumbuh anak.",
            'directory_description' => 'Di kelas, di perpustakaan, hingga di gerbang sekolah—ada tim yang siap mendampingi langkah kecil buah hati Anda.',
            'demo_profiles' => true,
        ];
    }

    public static function withDefaults(array $saved): array
    {
        $defaults = self::defaults();

        // Accept the earlier slug-keyed directory shape while keeping Firestore's people[0] format.
        if (! isset($saved['people']) && isset($saved['directory']) && is_array($saved['directory'])) {
            $savedPeople = $defaults['people'];
            foreach (self::IDS as $index => $id) {
                $person = $saved['directory'][$id] ?? $saved['directory'][$index] ?? null;
                if (is_array($person)) $savedPeople[$index] = array_replace($savedPeople[$index], $person);
            }
            $saved['people'] = $savedPeople;
            $structure = is_array($saved['structure'] ?? null) ? $saved['structure'] : [];
            if (isset($structure['head'])) $structure['head'] = self::profileIndex($structure['head']);
            $branches = is_array($structure['branches'] ?? null) ? $structure['branches'] : [];
            foreach ($branches as &$branch) {
                if (! is_array($branch)) continue;
                if (isset($branch['lead'])) $branch['lead'] = self::profileIndex($branch['lead']);
                $teams = is_array($branch['teams'] ?? null) ? $branch['teams'] : [];
                foreach ($teams as &$team) {
                    if (! is_array($team)) continue;
                    $members = is_array($team['members'] ?? null) ? $team['members'] : [];
                    $team['members'] = array_map(self::profileIndex(...), $members);
                }
                unset($team);
            }
            unset($branch);
            $structure['branches'] = $branches;
            $saved['structure'] = $structure;
            if (is_array($saved['featured_people'] ?? null)) {
                $saved['featured_people'] = array_map(self::profileIndex(...), $saved['featured_people']);
            }
        }

        // A Firestore directory is live school content. Mark it as such unless the
        // document explicitly opts into the sample-profile notice.
        if (isset($saved['people']) && is_array($saved['people']) && $saved['people'] !== []
            && ! array_key_exists('demo_profiles', $saved)) {
            $defaults['demo_profiles'] = false;
        }

        // Firestore data can be edited independently of the admin form. Ignore malformed
        // containers and keep a complete, indexable profile list for the Blade views.
        if (! isset($saved['people']) || ! is_array($saved['people']) || $saved['people'] === []) {
            $saved['people'] = $defaults['people'];
        } else {
            foreach ($saved['people'] as $index => $person) {
                if (! is_array($person) || ! isset($defaults['people'][$index])) {
                    unset($saved['people'][$index]);
                    continue;
                }
                $saved['people'][$index] = array_replace($defaults['people'][$index], $person);
            }
            if ($saved['people'] === []) $saved['people'] = $defaults['people'];
        }

        $data = self::merge($defaults, $saved);
        $people = [];
        foreach ($data['people'] as $index => $person) {
            if (! is_array($person)) continue;
            $person = array_replace($defaults['people'][$index] ?? $defaults['people'][0], $person);
            foreach (['name', 'role', 'description'] as $field) {
                if (! is_string($person[$field] ?? null)) {
                    $person[$field] = $defaults['people'][$index][$field] ?? '';
                }
            }
            if (! in_array($person['group'], ['pimpinan', 'guru', 'staf'], true)) {
                $person['group'] = $defaults['people'][$index]['group'] ?? 'staf';
            }
            $portrait = filter_var($person['portrait'] ?? null, FILTER_VALIDATE_INT);
            $person['portrait'] = $portrait !== false && $portrait >= 0 && $portrait <= 29 ? $portrait : 0;
            $person['photo'] = is_string($person['photo'] ?? null) ? $person['photo'] : null;
            $people[$index] = $person;
        }
        $data['people'] = array_values($people ?: $defaults['people']);
        $data['structure'] = self::normalizeStructure($data['structure'] ?? [], $defaults['structure'], count($data['people']));
        $data['featured_people'] = self::validIndices($data['featured_people'] ?? [], count($data['people']));
        $data['subjects'] = self::normalizeSubjects($data['subjects'] ?? [], $defaults['subjects']);

        if (! array_key_exists('featured_people', $saved)) {
            $featured = [];
            foreach (is_array($saved['team'] ?? null) ? $saved['team'] : [] as $member) {
                if (! is_array($member)) continue;
                $index = array_search($member['name'] ?? null, array_column($data['people'], 'name'), true);
                if ($index !== false) $featured[] = $index;
            }
            if ($featured) $data['featured_people'] = array_values(array_unique($featured));
        }

        unset($data['directory'], $data['team'], $data['id'], $data['schema_version']);
        return $data;
    }

    private static function merge(array $defaults, array $saved): array
    {
        if (array_is_list($defaults)) {
            if ($saved === []) return [];
            $merged = $defaults;
            foreach ($saved as $index => $value) {
                $merged[$index] = isset($defaults[$index]) && is_array($defaults[$index]) && is_array($value)
                    ? self::merge($defaults[$index], $value)
                    : $value;
            }
            return $merged;
        }

        foreach ($saved as $key => $value) {
            $defaults[$key] = isset($defaults[$key]) && is_array($defaults[$key]) && is_array($value)
                ? self::merge($defaults[$key], $value)
                : $value;
        }
        return $defaults;
    }

    private static function profileIndex(mixed $value): mixed
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) return (int) $value;
        $index = array_search($value, self::IDS, true);
        return $index === false ? $value : $index;
    }

    private static function validIndices(mixed $values, int $count): array
    {
        if (! is_array($values)) return [];
        $indices = [];
        foreach ($values as $value) {
            $index = filter_var($value, FILTER_VALIDATE_INT);
            if ($index !== false && $index >= 0 && $index < $count) $indices[] = $index;
        }
        return array_values(array_unique($indices));
    }

    private static function normalizeStructure(mixed $structure, array $fallback, int $count): array
    {
        if (! is_array($structure)) $structure = [];
        $head = filter_var($structure['head'] ?? null, FILTER_VALIDATE_INT);
        $structure['head'] = $head !== false && $head >= 0 && $head < $count ? $head : 0;
        $branches = is_array($structure['branches'] ?? null) ? $structure['branches'] : $fallback['branches'];
        $normalized = [];
        foreach ($branches as $index => $branch) {
            if (! is_array($branch)) continue;
            $base = $fallback['branches'][$index] ?? ['title'=>'Bidang sekolah', 'summary'=>'', 'tone'=>'blue', 'lead'=>0, 'teams'=>[]];
            $branch = array_replace($base, $branch);
            $branch['title'] = is_string($branch['title']) ? $branch['title'] : $base['title'];
            $branch['summary'] = is_string($branch['summary']) ? $branch['summary'] : $base['summary'];
            if (! in_array($branch['tone'], ['blue', 'green', 'yellow'], true)) $branch['tone'] = 'blue';
            $lead = filter_var($branch['lead'], FILTER_VALIDATE_INT);
            $branch['lead'] = $lead !== false && $lead >= 0 && $lead < $count ? $lead : 0;
            $teams = is_array($branch['teams']) ? $branch['teams'] : [];
            $branch['teams'] = [];
            foreach ($teams as $team) {
                if (! is_array($team)) continue;
                $branch['teams'][] = [
                    'title' => is_string($team['title'] ?? null) ? $team['title'] : 'Tim sekolah',
                    'members' => self::validIndices($team['members'] ?? [], $count),
                ];
            }
            $normalized[] = $branch;
        }
        $structure['branches'] = $normalized;
        return $structure;
    }

    private static function normalizeSubjects(mixed $subjects, array $fallback): array
    {
        if (! is_array($subjects) || $subjects === []) return $fallback;
        $normalized = [];
        foreach ($subjects as $index => $subject) {
            if (! is_array($subject)) continue;
            $base = $fallback[$index] ?? ['title'=>'Mata pelajaran', 'by'=>'Tim pengajar', 'text'=>''];
            $normalized[] = [
                'title' => is_string($subject['title'] ?? null) ? $subject['title'] : $base['title'],
                'by' => is_string($subject['by'] ?? null) ? $subject['by'] : $base['by'],
                'text' => is_string($subject['text'] ?? null) ? $subject['text'] : $base['text'],
            ];
        }
        return $normalized ?: $fallback;
    }

    public static function principal(array $data): array
    {
        $data = self::withDefaults($data);
        return $data['people'][$data['structure']['head']] ?? $data['people'][0];
    }

    public static function homepagePeople(array $data): array
    {
        $data = self::withDefaults($data);
        $indices = array_unique([$data['structure']['head'], ...$data['featured_people']]);
        return array_values(array_filter(array_map(fn ($index) => $data['people'][$index] ?? null, $indices)));
    }

    public static function validate(array $data): void
    {
        $indices = array_keys($data['people'] ?? []);
        Validator::make($data, [
            'people' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'people.*.name' => 'required|string|max:150',
            'people.*.role' => 'required|string|max:150',
            'people.*.description' => 'required|string|max:2000',
            'people.*.group' => ['required', Rule::in(['pimpinan', 'guru', 'staf'])],
            'people.*.portrait' => 'required|integer|min:0|max:29',
            'people.*.photo' => ['nullable', 'string', 'regex:#^/(?:assets/(?:design|team)|media)/[a-zA-Z0-9_-]+\.(?:png|jpe?g|webp)$#'],
            'featured_people' => 'present|array|list|max:100',
            'featured_people.*' => ['required', 'integer', 'distinct', Rule::in($indices)],
            'subjects' => 'present|array|list|max:100',
            'subjects.*.title' => 'required|string|max:150',
            'subjects.*.by' => 'required|string|max:300',
            'subjects.*.text' => 'required|string|max:2000',
            'structure.head' => ['required', 'integer', Rule::in($indices)],
            'structure.branches' => 'present|array|list|max:30',
            'structure.branches.*.title' => 'required|string|max:150',
            'structure.branches.*.summary' => 'required|string|max:2000',
            'structure.branches.*.tone' => ['required', Rule::in(['blue', 'green', 'yellow'])],
            'structure.branches.*.lead' => ['required', 'integer', Rule::in($indices)],
            'structure.branches.*.teams' => 'present|array|list|max:100',
            'structure.branches.*.teams.*.title' => 'required|string|max:150',
            'structure.branches.*.teams.*.members' => 'present|array|list|max:100',
            'structure.branches.*.teams.*.members.*' => ['required', 'integer', Rule::in($indices)],
            'directory_eyebrow' => 'required|string|max:150',
            'directory_title' => 'required|string|max:300',
            'directory_description' => 'required|string|max:2000',
            'demo_profiles' => 'required|boolean',
        ], ['in' => 'Pilihan :attribute tidak tersedia. Pilih anggota atau kategori yang ada.'])->validate();
    }
}
