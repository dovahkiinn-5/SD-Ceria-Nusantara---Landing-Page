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
    }
}
