<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Illuminate\Support\Facades\Cache;

class SchoolContent
{
    public function __construct(private DocumentStore $store) {}
    public function defaults(): array
    {
        $content = require resource_path('content/site.php');
<<<<<<< HEAD
        $content['teachers'] = TeacherDirectory::withDefaults($content['teachers']);
=======
        $content['teachers'] = (new TeacherDirectory)->normalize($content['teachers']);
        unset($content['home']['principal_name']);
>>>>>>> 2812c58f61084d9e538e7e37c1080590d1f8a5e7
        return $content;
    }
    public function all(): array
    {
<<<<<<< HEAD
        $all = Cache::remember('school-content-'.config('school.store'), 300, function () {
=======
        $content = Cache::remember('school-content-'.config('school.store'), 300, function () {
>>>>>>> 2812c58f61084d9e538e7e37c1080590d1f8a5e7
            $content = $this->defaults();
            foreach ($content as $key => $default) {
                if ($key === 'teachers') continue;
                $saved = $this->store->get('content', $key);
                if ($saved) {
                    unset($saved['id']);
<<<<<<< HEAD
                    if ($key === 'teachers') $saved = TeacherDirectory::withDefaults($saved);
                    $content[$key] = self::mergeDefaults($default, $saved);
=======
                    $content[$key] = array_replace_recursive($default, $saved);
>>>>>>> 2812c58f61084d9e538e7e37c1080590d1f8a5e7
                }
            }
            return $content;
        });
<<<<<<< HEAD
        // Old cached documents may predate the directory contract.
        $all['teachers'] = TeacherDirectory::withDefaults($all['teachers']);
        unset($all['home']['principal_name']);
        return $all;
    }

    public static function mergeDefaults(array $defaults, array $saved): array
    {
        foreach ($saved as $key => $value) {
            $defaults[$key] = is_array($value) && $value !== [] && !array_is_list($value)
                && isset($defaults[$key]) && is_array($defaults[$key]) && !array_is_list($defaults[$key])
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
=======

        // Read profiles live so edits in the Firebase console also appear on reload.
        $saved = $this->store->get('content', 'teachers') ?? [];
        $base = (require resource_path('content/site.php'))['teachers'];
        $content['teachers'] = (new TeacherDirectory)->normalize(array_replace($base, $saved));
        unset($content['home']['principal_name']);
        return $content;
    }
    public function save(string $section, array $data): void
    {
        if ($section === 'teachers') $data = (new TeacherDirectory)->normalize($data);
>>>>>>> 2812c58f61084d9e538e7e37c1080590d1f8a5e7
        if ($section === 'home') unset($data['principal_name']);
        $this->store->put('content', $section, $data);
        Cache::forget('school-content-'.config('school.store'));
    }

    public function editableFields(string $section, array $data): array
    {
        $fields = \Illuminate\Support\Arr::dot($data);
        if ($section !== 'teachers') return $fields;
        return array_filter($fields, static fn ($key) =>
            !str_starts_with($key, 'structure.') && $key !== 'schema_version'
            && !preg_match('/\.(role|group|portrait)$|\.members\./', $key), ARRAY_FILTER_USE_KEY);
    }
}
