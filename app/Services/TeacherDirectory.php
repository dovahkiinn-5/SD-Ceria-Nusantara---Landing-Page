<?php

namespace App\Services;

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
        $subjectMembers = [
            0 => array_map(fn ($grade) => 'wali-kelas-'.$grade, range(1, 6)),
            1 => array_map(fn ($grade) => 'wali-kelas-'.$grade, range(1, 6)),
            2 => array_map(fn ($grade) => 'wali-kelas-'.$grade, range(1, 6)),
            3 => array_map(fn ($grade) => 'wali-kelas-'.$grade, range(1, 6)),
            4 => ['guru-agama-islam', 'guru-agama-kristen', 'guru-agama-katolik', 'guru-agama-hindu', 'guru-agama-buddha', 'guru-agama-khonghucu'],
            5 => ['guru-pjok'],
            6 => ['guru-seni-budaya'],
            7 => ['guru-bahasa-inggris'],
            8 => ['guru-muatan-lokal'],
        ];
        foreach ($subjectMembers as $index => $members) {
            $subjects[$index]['members'] = $members;
            unset($subjects[$index]['by']);
        }
        $featuredPeople = array_map(fn ($index) => self::IDS[$index], [0, 1, 2, 3, 16, 21]);
        return [
            'schema_version' => 2,
            'directory' => $directory,
            'featured_people' => $featuredPeople,
            'structure' => $structure,
            'subjects' => $subjects,
        ];
    }

    /** Convert legacy numeric profiles once, preserving names/photos but restoring school positions. */
    public function normalize(array $data): array
    {
        $defaults = $this->defaults();
        if (($data['schema_version'] ?? null) !== 2) {
            $legacyDefaults = (require resource_path('content/site.php'))['teachers']['team'];
            $directory = $defaults['directory'];
            foreach (self::IDS as $index => $id) {
                $saved = $data['people'][$index] ?? $data['directory'][$index] ?? $data['directory'][$id] ?? [];
                if (is_array($saved)) $directory[$id] = array_replace($directory[$id], $saved);
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
            $structure = $this->mergeDefaults($defaults['structure'], $data['structure'] ?? []);
            $structure['head'] = $this->profileId($structure['head'] ?? $defaults['structure']['head']);
            foreach ($structure['branches'] as &$branch) {
                if (isset($branch['lead'])) $branch['lead'] = $this->profileId($branch['lead']);
                foreach ($branch['teams'] as &$team) {
                    $team['members'] = array_map($this->profileId(...), $team['members'] ?? []);
                }
                unset($team);
            }
            unset($branch);
            $featuredPeople = array_map($this->profileId(...), $data['featured_people'] ?? $defaults['featured_people']);
            $data = array_replace($defaults, $data, [
                'schema_version' => 2,
                'directory' => $directory,
                'featured_people' => $featuredPeople,
                'structure' => $structure,
            ]);
        }
        $data = $this->mergeDefaults($defaults, $data);
        foreach ($data['subjects'] as &$subject) {
            unset($subject['by']);
        }
        unset($subject);
        unset($data['team'], $data['people'], $data['id']);
        $this->validate($data);
        // Both the editor and public directory follow the organization chart order.
        $ordered = [];
        foreach ($this->positions($data['structure']) as $id) $ordered[$id] = $data['directory'][$id];
        $data['directory'] = $ordered;
        return $data;
    }

    private function mergeDefaults(array $defaults, array $saved): array
    {
        if (array_is_list($defaults)) {
            $merged = [];
            foreach ($saved as $index => $value) {
                $merged[$index] = isset($defaults[$index]) && is_array($defaults[$index]) && is_array($value)
                    ? $this->mergeDefaults($defaults[$index], $value)
                    : $value;
            }
            return $merged;
        }

        $merged = $defaults;
        foreach ($saved as $key => $value) {
            if (! array_key_exists($key, $defaults)) {
                $merged[$key] = $value;
            } elseif (is_array($defaults[$key]) && is_array($value)) {
                $merged[$key] = $this->mergeDefaults($defaults[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    private function profileId(mixed $value): mixed
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return self::IDS[(int) $value] ?? $value;
        }
        return $value;
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
        if (array_diff($data['featured_people'] ?? [], array_keys($directory))) {
            throw ValidationException::withMessages(['values' => 'Profil unggulan tidak ditemukan dalam direktori guru dan staf.']);
        }
    }
}
