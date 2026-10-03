<?php

namespace Tests\Support;

use App\Services\FirestoreFileStorage;
use Illuminate\Http\UploadedFile;

class FakeFirestoreFileStorage extends FirestoreFileStorage
{
    public array $objects = [];

    public function __construct() {}

    public function put(string $path, string $contents, string $contentType = 'application/octet-stream'): array
    {
        $this->objects[$path] = $contents;

        return ['name' => $path, 'contentType' => $contentType];
    }

    public function putUploadedFile(string $path, UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());

        return $this->put($path, $contents === false ? '' : $contents, $file->getMimeType() ?: 'application/octet-stream');
    }

    public function exists(string $path): bool
    {
        return array_key_exists($path, $this->objects);
    }

    public function get(string $path): ?array
    {
        if (! $this->exists($path)) {
            return null;
        }

        return [
            'contents' => $this->objects[$path],
            'content_type' => str_ends_with($path, '.png') ? 'image/png' : 'application/octet-stream',
            'size' => strlen($this->objects[$path]),
        ];
    }

    public function delete(string $path): bool
    {
        $exists = $this->exists($path);
        unset($this->objects[$path]);

        return $exists;
    }

}
