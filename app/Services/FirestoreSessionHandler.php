<?php

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Support\Facades\Log;
use SessionHandlerInterface;

class FirestoreSessionHandler implements SessionHandlerInterface
{
    private FileSessionHandler $fallback;

    public function __construct(private FirestoreDocumentStore $documents, private int $minutes, private string $namespace)
    {
        $path = storage_path('framework/sessions');
        if (! is_dir($path)) @mkdir($path, 0775, true);
        $this->fallback = new FileSessionHandler(new Filesystem, $path, $minutes);
    }
    private function valid(string $id): bool { return (bool) preg_match('/^[a-zA-Z0-9,-]{20,128}$/D', $id); }
    private function id(string $id): string { return hash('sha256', $this->namespace.':'.$id); }
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false
    {
        if (!$this->valid($id)) return '';
        try {
            $record = $this->documents->get('runtime_sessions', $this->id($id));
            if ($record && ($record['expires_at'] ?? 0) > now()->timestamp) {
                $payload = base64_decode($record['payload'] ?? '', true);
                if ($payload !== false) return $payload;
            }
        } catch (\Throwable $exception) {
            Log::warning('Sesi Firestore tidak dapat dibaca; memakai sesi lokal sementara.', ['exception' => $exception::class]);
        }
        return $this->fallback->read($id);
    }
    public function write(string $id, string $data): bool
    {
        if (!$this->valid($id)) return false;
        $localWritten = $this->fallback->write($id, $data);
        try {
            $this->documents->put('runtime_sessions', $this->id($id), [
                'namespace'=>hash('sha256', $this->namespace),
                'payload'=>base64_encode($data), 'expires_at'=>now()->timestamp + $this->minutes * 60,
            ]);
            return true;
        } catch (\Throwable $exception) {
            Log::warning('Sesi Firestore tidak dapat disimpan; sesi disimpan secara lokal.', ['exception' => $exception::class]);
            return $localWritten;
        }
    }
    public function destroy(string $id): bool
    {
        if (!$this->valid($id)) return false;
        $localDeleted = $this->fallback->destroy($id);
        try {
            $this->documents->delete('runtime_sessions', $this->id($id));
            return true;
        } catch (\Throwable $exception) {
            Log::warning('Sesi Firestore tidak dapat dihapus; sesi lokal sudah dihapus.', ['exception' => $exception::class]);
            return $localDeleted;
        }
    }
    public function gc(int $max_lifetime): int|false
    {
        $deleted = (int) $this->fallback->gc($max_lifetime);
        try {
            foreach ($this->documents->expiredSnapshots('runtime_sessions', now()->timestamp) as $snapshot) {
                if (($snapshot['data']['namespace'] ?? null) === hash('sha256', $this->namespace)) {
                    $deleted += (int) $this->documents->deleteIfUnchanged('runtime_sessions', $snapshot['data']['id'], $snapshot['version']);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Pembersihan sesi Firestore gagal; sesi lokal tetap dibersihkan.', ['exception' => $exception::class]);
        }
        return $deleted;
    }
}
