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
        $cache = Cache::store('file');
        $key = 'school-content-'.config('school.store');
        $legacy = $cache->get($key);
        if (! is_array($legacy)) $legacy = $cache->get('school-content-last-known');

        // The former cache used this key for five minutes. Clear it once after
        // the freshness change, keeping its value as a fallback during outages.
        if ($cache->get('school-content-cache-version') !== 2) {
            if (is_array($legacy)) $cache->put('school-content-last-known', $legacy, now()->addDays(30));
            $cache->forget($key);
            $cache->forever('school-content-cache-version', 2);
        }

        // A short, synchronous cache is predictable on both PHP-FPM and the
        // built-in local server. All Firestore documents are refreshed together
        // when it expires, so edits from another admin process appear quickly.
        return $cache->remember($key, 30, fn () => $this->fetchPublishedContent($legacy));
    }

    private function fetchPublishedContent(?array $legacy = null): array
    {
        $content = $this->defaults();
        $localCache = Cache::store('file');
        $allSectionsAvailable = true;
        $savedSections = [];

        try {
            $savedSections = $this->store->getMany('content', array_keys($content));
        } catch (\Throwable $exception) {
            $allSectionsAvailable = false;
            Log::warning('Konten Firestore tidak dapat dibaca; memakai konten terakhir yang tersimpan lokal.', [
                'exception' => $exception::class,
            ]);
        }

        foreach ($content as $section => $default) {
            $saved = $savedSections[$section] ?? null;
            if (is_array($saved)) {
                unset($saved['id']);
                $content[$section] = self::mergeDefaults($default, $saved);
                $localCache->put($this->lastKnownKey($section), $saved, now()->addDays(30));
            } elseif (! $allSectionsAvailable) {
                // Batch reads failed; use the matching last-known document below.
                $saved = null;
            } else {
                continue;
            }

            if (! is_array($saved)) {
                $allSectionsAvailable = false;
                $saved = $localCache->get($this->lastKnownKey($section));
                if (is_array($saved)) $content[$section] = self::mergeDefaults($default, $saved);
            }
        }

        // Teacher directory also comes from the same Firestore read as every
        // other landing page section so the admin and public view cannot drift.
        $saved = $savedSections['teachers'] ?? null;
        if (is_array($saved)) {
            unset($saved['id']);
            $localCache->put($this->lastKnownKey('teachers'), $saved, now()->addDays(30));
        } elseif ($allSectionsAvailable) {
            $saved = [];
        } else {
            $saved = $localCache->get($this->lastKnownKey('teachers'), []);
        }
        $content['teachers'] = TeacherDirectory::withDefaults(self::mergeDefaults(
            $content['teachers'], is_array($saved) ? $saved : []
        ));
        unset($content['home']['principal_name']);
        $content = self::restoreHomeHeroIfContentIsInvalid($content);

        if ($allSectionsAvailable) {
            $localCache->put('school-content-last-known', $content, now()->addDays(30));
        } elseif ($lastKnown = $localCache->get('school-content-last-known', $legacy)) {
            // A transient Firestore error should not combine old and new sections.
            $content = $lastKnown;
        }

        return $content;
    }

    /** Restore the bundled school hero when editorial or placeholder text was published by mistake. */
    private static function restoreHomeHeroIfContentIsInvalid(array $content): array
    {
        $home = $content['home'] ?? [];
        $heroCopy = mb_strtolower(implode(' ', array_filter([
            $home['title'] ?? '',
            $home['description'] ?? '',
        ], 'is_string')));

        if (! preg_match('/melankolis|guru jarang masuk|tim begadang|website hanya dikerjakan/i', $heroCopy)) {
            return $content;
        }

        $defaults = require resource_path('content/site.php');
        foreach (['eyebrow', 'title', 'description', 'tablet_description', 'image', 'campus_image', 'classroom_image'] as $key) {
            if (array_key_exists($key, $defaults['home'])) {
                $content['home'][$key] = $defaults['home'][$key];
            }
        }
        $content['settings']['founded'] = $defaults['settings']['founded'];

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
            $data = self::mergeDefaults($this->defaults()['teachers'], $data);
            TeacherDirectory::validate($data);
            $data = TeacherDirectory::withDefaults($data);
        }
        if ($section === 'home') unset($data['principal_name']);
        $this->store->put('content', $section, $data);
        $cache = Cache::store('file');
        $key = 'school-content-'.config('school.store');
        $cache->forget($key);
        $cache->put($this->lastKnownKey($section), $data, now()->addDays(30));

        // Keep this process's published snapshot in sync too. Other web servers
        // will refresh their own short cache from Firestore within 30 seconds.
        $published = $cache->get('school-content-last-known', $this->defaults());
        $published[$section] = $section === 'teachers'
            ? TeacherDirectory::withDefaults($data)
            : self::mergeDefaults($published[$section] ?? $this->defaults()[$section], $data);
        unset($published['home']['principal_name']);
        $cache->put('school-content-last-known', $published, now()->addDays(30));
        $cache->put($key, $published, 30);
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

    private function lastKnownKey(string $section): string
    {
        return 'firestore-content-'.$section.'-last-known';
    }
}
