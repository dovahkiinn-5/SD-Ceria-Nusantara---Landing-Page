<?php

namespace App\Services;

<<<<<<< HEAD
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Shared content contract: keep this class and the fallback resource in both projects.
class TeacherDirectory
{
    public static function defaults(): array
    {
        return (require resource_path('content/staff-directory.php')) + [
            'directory_eyebrow' => 'KENALI KELUARGA SEKOLAH KAMI',
            'directory_title' => "Wajah ramah,\nteman tumbuh anak.",
            'directory_description' => 'Di kelas, di perpustakaan, hingga di gerbang sekolah—ada tim yang siap mendampingi langkah kecil buah hati Anda.',
            'demo_profiles' => true,
        ];
    }

    public static function withDefaults(array $saved): array
    {
        // The organization chart owns the profiles. Legacy copies must never override it.
        if (!isset($saved['people']) && !empty($saved['directory'])) $saved['people'] = $saved['directory'];
        $data = $saved + self::defaults();
        $data['people'] = array_map(function (array $person) {
            if (isset($person['portrait']) && ctype_digit((string) $person['portrait'])) $person['portrait'] = (int) $person['portrait'];
            return $person + ['photo'=>null];
        }, $data['people']);
        if (!array_key_exists('featured_people', $data)) {
            $featured = [];
            foreach ($saved['team'] ?? [] as $member) {
                $index = array_search($member['name'], array_column($data['people'], 'name'), true);
                if ($index === false) {
                    // Old directory and organization profiles share a portrait identity.
                    $old = collect($saved['directory'] ?? [])->firstWhere('name', $member['name']);
                    $matches = $old && isset($old['portrait'])
                        ? array_keys(array_filter($data['people'], fn ($person) => $person['portrait'] === (int) $old['portrait'])) : [];
                    if (count($matches) === 1) $index = $matches[0];
                }
                if ($index !== false) $featured[] = $index;
            }
            $data['featured_people'] = array_values(array_unique($featured ?: array_column($data['structure']['branches'], 'lead')));
        }
        if (isset($data['subjects']) && is_array($data['subjects'])) {
            $defaultSubjects = self::defaults()['subjects'];
            foreach ($data['subjects'] as $i => &$sub) {
                if (is_array($sub) && !isset($sub['by'])) $sub['by'] = $defaultSubjects[$i]['by'] ?? '';
            }
            unset($sub);
        }
        unset($data['directory'], $data['team']);
        return $data;
    }

    public static function principal(array $data): array
    {
        $data = self::withDefaults($data);
        return $data['people'][$data['structure']['head']];
    }

    public static function homepagePeople(array $data): array
    {
        $data = self::withDefaults($data);
        $indices = array_unique([$data['structure']['head'], ...$data['featured_people']]);
        return array_values(array_map(fn ($index) => $data['people'][$index], $indices));
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
=======
use Illuminate\Validation\ValidationException;

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
    private const LEGACY_TEAM = [0, 1, 2, 3, 16, 21];

    public function defaults(): array
    {
        $source = require resource_path('content/staff-directory.php');
        $directory = [];
        foreach ($source['people'] as $index => $person) {
            $person['photo'] ??= '';
            $person['portrait'] = (string) $person['portrait'];
            $directory[self::IDS[$index]] = $person;
        }
        $structure = $source['structure'];
        $structure['head'] = self::IDS[$structure['head']];
        foreach ($structure['branches'] as &$branch) {
            $branch['lead'] = self::IDS[$branch['lead']];
            foreach ($branch['teams'] as &$team) {
                $team['members'] = array_map(fn ($index) => self::IDS[$index], $team['members']);
            }
            unset($team);
        }
        unset($branch);
        $subjects = $source['subjects'];
        foreach ([5 => 'guru-pjok', 6 => 'guru-seni-budaya', 7 => 'guru-bahasa-inggris', 8 => 'guru-muatan-lokal'] as $index => $id) {
            $subjects[$index]['members'] = [$id];
            unset($subjects[$index]['by']);
        }
        return ['schema_version' => 2, 'directory' => $directory, 'structure' => $structure, 'subjects' => $subjects];
    }

    /** Convert legacy numeric profiles once, preserving names/photos but restoring school positions. */
    public function normalize(array $data): array
    {
        $defaults = $this->defaults();
        if (($data['schema_version'] ?? null) !== 2) {
            $legacyDefaults = (require resource_path('content/site.php'))['teachers']['team'];
            $directory = $defaults['directory'];
            foreach (self::IDS as $index => $id) {
                $saved = $data['directory'][$index] ?? $data['directory'][$id] ?? [];
                foreach (['name', 'photo', 'description'] as $field) {
                    if (isset($saved[$field])) $directory[$id][$field] = $saved[$field];
                }
            }
            foreach (self::LEGACY_TEAM as $old => $index) {
                $id = self::IDS[$index];
                foreach (['name', 'photo'] as $field) {
                    $legacy = $data['team'][$old][$field] ?? null;
                    if ($legacy !== null && $legacy !== $legacyDefaults[$old][$field]
                        && $directory[$id][$field] === $defaults['directory'][$id][$field]) {
                        $directory[$id][$field] = $legacy;
                    }
                }
            }
            $data = array_replace($data, $defaults, ['directory' => $directory]);
        }
        unset($data['team'], $data['id']);
        $this->validate($data);
        // Both the editor and public directory follow the organization chart order.
        $ordered = [];
        foreach ($this->positions($data['structure']) as $id) $ordered[$id] = $data['directory'][$id];
        $data['directory'] = $ordered;
        return $data;
    }

    public function positions(array $structure): array
    {
        $ids = [$structure['head']];
        foreach ($structure['branches'] as $branch) {
            $ids[] = $branch['lead'];
            foreach ($branch['teams'] as $team) array_push($ids, ...$team['members']);
        }
        return $ids;
    }

    public function validate(array $data): void
    {
        $structure = $data['structure'] ?? [];
        $directory = $data['directory'] ?? [];
        if (!isset($structure['head'], $structure['branches']) || !is_array($directory)) {
            throw ValidationException::withMessages(['values' => 'Struktur guru dan staf belum lengkap.']);
        }
        $ids = $this->positions($structure);
        if (count($ids) !== count(array_unique($ids)) || count($ids) !== count($directory)
            || array_diff($ids, array_keys($directory)) || array_diff(array_keys($directory), $ids)) {
            throw ValidationException::withMessages(['values' => 'Setiap profil harus muncul tepat satu kali dalam struktur sekolah.']);
        }
        $heads = array_filter($directory, fn ($person) => mb_strtolower(trim($person['role'] ?? '')) === 'kepala sekolah');
        if (count($heads) !== 1 || !isset($heads[$structure['head']])) {
            throw ValidationException::withMessages(['values' => 'Struktur sekolah harus memiliki tepat satu Kepala Sekolah.']);
        }
        foreach ($directory as $person) {
            if (!in_array($person['group'] ?? '', ['pimpinan', 'guru', 'staf'], true)) {
                throw ValidationException::withMessages(['values' => 'Kelompok guru/staf tidak valid.']);
            }
        }
        foreach ($data['subjects'] ?? [] as $subject) {
            if (array_diff($subject['members'] ?? [], array_keys($directory))) {
                throw ValidationException::withMessages(['values' => 'Pengajar mata pelajaran tidak ditemukan dalam struktur sekolah.']);
            }
        }
>>>>>>> 2812c58f61084d9e538e7e37c1080590d1f8a5e7
    }
}
