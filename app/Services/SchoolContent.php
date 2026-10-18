<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SchoolContent
{
    public function __construct(private DocumentStore $store) {}

    public function defaults(): array
    {
        $content = require resource_path('content/site.php');
        $content['teachers'] = TeacherDirectory::withDefaults($content['teachers']);
        unset($content['home']['principal_name']);
        return $content;
    }

    public function all(): array
    {
        $content = Cache::store('file')->remember('school-content-'.config('school.store'), 300, function () {
            $content = $this->defaults();
            $firestoreAvailable = true;
            foreach ($content as $key => $default) {
                if ($key === 'teachers') continue;
                if (! $firestoreAvailable) break;
                try {
                    $saved = $this->store->get('content', $key);
                } catch (\Throwable $exception) {
                    $firestoreAvailable = false;
                    Log::warning('Konten Firestore tidak dapat dibaca; memakai konten lokal sementara.', [
                        'section' => $key, 'exception' => $exception::class,
                    ]);
                    $saved = null;
                }
                if ($saved) {
                    unset($saved['id']);
                    $content[$key] = self::mergeDefaults($default, $saved);
                }
            }
            return $content;
        });

        // Read staff directly every request so Firebase Console edits show immediately.
        // Keep a local last-known copy for temporary credential/network outages.
        try {
            $saved = $this->store->get('content', 'teachers') ?? [];
            Cache::store('file')->put('firestore-content-teachers-last-known', $saved, now()->addDays(30));
        } catch (\Throwable $exception) {
            Log::warning('Data guru Firestore tidak dapat dibaca; memakai salinan lokal terakhir.', [
                'exception' => $exception::class,
            ]);
            $saved = Cache::store('file')->get('firestore-content-teachers-last-known', []);
        }
        unset($saved['id']);
        $content['teachers'] = TeacherDirectory::withDefaults(self::mergeDefaults(
            $this->defaults()['teachers'], $saved
        ));
        unset($content['home']['principal_name']);
        return $content;
    }

    public static function mergeDefaults(array $defaults, array $saved): array
    {
        foreach ($saved as $key => $value) {
            $defaults[$key] = is_array($value) && $value !== [] && ! array_is_list($value)
                && isset($defaults[$key]) && is_array($defaults[$key]) && ! array_is_list($defaults[$key])
                ? self::mergeDefaults($defaults[$key], $value) : $value;
        }
        return $defaults;
    }

    public function save(string $section, array $data): void
    {
        if ($section === 'teachers') {
            $data = TeacherDirectory::withDefaults($data);
            TeacherDirectory::validate($data);
        }
        if ($section === 'home') unset($data['principal_name']);
        $this->store->put('content', $section, $data);
        Cache::store('file')->forget('school-content-'.config('school.store'));
    }

    public function editableFields(string $section, array $data): array
    {
        $fields = Arr::dot($data);
        if ($section !== 'teachers') return $fields;
        return array_filter($fields, static fn ($key) =>
            ! str_starts_with($key, 'structure.') && $key !== 'demo_profiles'
            && ! str_starts_with($key, 'featured_people.')
            && ! preg_match('/\.(role|group|portrait)$|\.members\./', $key), ARRAY_FILTER_USE_KEY);
    }
}
