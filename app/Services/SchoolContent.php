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
        $content['teachers'] = TeacherDirectory::withDefaults($content['teachers']);
        return $content;
    }
    public function all(): array
    {
        $all = Cache::remember('school-content-'.config('school.store'), 300, function () {
            $content = $this->defaults();
            foreach ($content as $key => $default) {
                $saved = $this->store->get('content', $key);
                if ($saved) {
                    unset($saved['id']);
                    if ($key === 'teachers') $saved = TeacherDirectory::withDefaults($saved);
                    $content[$key] = self::mergeDefaults($default, $saved);
                }
            }
            return $content;
        });
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
        if ($section === 'home') unset($data['principal_name']);
        $this->store->put('content', $section, $data);
        Cache::forget('school-content-'.config('school.store'));
    }
}
