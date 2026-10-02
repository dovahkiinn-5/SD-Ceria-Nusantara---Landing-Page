<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Illuminate\Support\Facades\Cache;

class SchoolContent
{
    public function __construct(private DocumentStore $store) {}
    public function defaults(): array { return require resource_path('content/site.php'); }
    public function all(): array
    {
        return Cache::remember('school-content-'.config('school.store'), 300, function () {
            $content = $this->defaults();
            foreach ($content as $key => $default) {
                $saved = $this->store->get('content', $key);
                if ($saved) { unset($saved['id']); $content[$key] = array_replace_recursive($default, $saved); }
            }
            return $content;
        });
    }
    public function save(string $section, array $data): void
    {
        $this->store->put('content', $section, $data);
        Cache::forget('school-content-'.config('school.store'));
    }
}
