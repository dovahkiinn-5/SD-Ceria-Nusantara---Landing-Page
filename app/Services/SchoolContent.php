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
        $content['teachers'] = (new TeacherDirectory)->normalize($content['teachers']);
        unset($content['home']['principal_name']);
        return $content;
    }
    public function all(): array
    {
        $content = Cache::remember('school-content-'.config('school.store'), 300, function () {
            $content = $this->defaults();
            foreach ($content as $key => $default) {
                if ($key === 'teachers') continue;
                $saved = $this->store->get('content', $key);
                if ($saved) {
                    unset($saved['id']);
                    $content[$key] = array_replace_recursive($default, $saved);
                }
            }
            return $content;
        });

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
