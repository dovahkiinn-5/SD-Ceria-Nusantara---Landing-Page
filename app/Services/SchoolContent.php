<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Illuminate\Support\Facades\Cache;

class SchoolContent
{
    public function __construct(private DocumentStore $store) {}
    public function defaults(): array
    {
        $defaults = require resource_path('content/site.php');
        $defaults['home'] += ['campus_image'=>'/assets/design/sd-ceria-nusantara-campus.png'];
        return $defaults;
    }
    public function all(): array
    {
        $content = Cache::remember('school-content-'.config('school.store'), 300, function () {
            $content = $this->defaults();
            foreach ($content as $key => $default) {
                $saved = $this->store->get('content', $key);
                if ($saved) { unset($saved['id']); $content[$key] = $this->merge($default, $saved); }
            }
            return $content;
        });
        $content['teachers'] = TeacherDirectory::withDefaults($content['teachers']);
        $content['home'] += ['campus_image'=>'/assets/design/sd-ceria-nusantara-campus.png'];
        return $content;
    }
    // Maps inherit defaults; lists replace complete lists so removed cards stay removed.
    private function merge(array $defaults, array $saved): array
    {
        foreach ($saved as $key => $value) {
            $defaults[$key] = isset($defaults[$key]) && is_array($defaults[$key]) && is_array($value)
                && !array_is_list($defaults[$key]) && !array_is_list($value)
                ? $this->merge($defaults[$key], $value) : $value;
        }
        return $defaults;
    }

    public function save(string $section, array $data): void
    {
        $this->store->put('content', $section, $data);
        Cache::forget('school-content-'.config('school.store'));
    }
}
