<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirestoreDocumentStore implements DocumentStore
{
    private function endpoint(): string
    {
        return 'https://firestore.googleapis.com/v1/projects/'.rawurlencode(config('school.firebase_project')).'/databases/'.rawurlencode(config('school.firebase_database')).'/documents';
    }
    private function client()
    {
        $project = config('school.firebase_project');
        $credentials = config('school.firebase_credentials');
        if (! $project || ! is_file($credentials)) {
            throw new RuntimeException('Konfigurasi Firebase belum lengkap. Isi FIREBASE_PROJECT_ID dan FIREBASE_CREDENTIALS.');
        }
        $caBundle = config('school.firebase_ca_bundle');
        if ($caBundle !== null && $caBundle !== '' && (! is_string($caBundle) || ! is_readable($caBundle))) {
            throw new RuntimeException('FIREBASE_CA_BUNDLE harus berupa path berkas sertifikat CA yang dapat dibaca.');
        }
        $options = ['verify' => $caBundle ?: true, 'timeout' => 20, 'connect_timeout' => 10];
        $token = Cache::remember('firebase-access-'.hash('sha256', $project.$credentials), 3000, function () use ($credentials, $options) {
            $auth = new ServiceAccountCredentials('https://www.googleapis.com/auth/datastore', $credentials);
            $handler = HttpHandlerFactory::build(new HttpClient($options), false);
            $token = $auth->fetchAuthToken($handler);
            return $token['access_token'] ?? throw new RuntimeException('Autentikasi Firebase gagal.');
        });
        return Http::withToken($token)->acceptJson()->withOptions($options)
            ->baseUrl($this->endpoint());
    }

    private function path(string $collection, string $id): string
    {
        return '/'.rawurlencode($collection).'/'.rawurlencode($id);
    }

    public static function encode(mixed $value): array
    {
        return match (true) {
            is_null($value) => ['nullValue' => null],
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_array($value) && array_is_list($value) => ['arrayValue' => ['values' => array_map(self::encode(...), $value)]],
            is_array($value) => ['mapValue' => ['fields' => array_map(self::encode(...), $value)]],
            default => ['stringValue' => (string) $value],
        };
    }

    public static function decode(array $value): mixed
    {
        return match (array_key_first($value)) {
            'nullValue' => null,
            'integerValue' => (int) $value['integerValue'],
            'doubleValue' => (float) $value['doubleValue'],
            'booleanValue' => $value['booleanValue'],
            'mapValue' => array_map(self::decode(...), $value['mapValue']['fields'] ?? []),
            'arrayValue' => array_map(self::decode(...), $value['arrayValue']['values'] ?? []),
            default => reset($value),
        };
    }

    private function document(array $doc): array
    {
        return ['id' => basename($doc['name'])] + array_map(self::decode(...), $doc['fields'] ?? []);
    }

    private function fields(array $data): array
    {
        unset($data['id']);
        return ['fields' => (object) array_map(self::encode(...), $data)];
    }

    public function get(string $collection, string $id): ?array
    {
        $response = $this->client()->get($this->path($collection, $id));
        return $response->status() === 404 ? null : $this->document($response->throw()->json());
    }

    public function put(string $collection, string $id, array $data): void
    {
        $this->client()->patch($this->path($collection, $id), $this->fields($data))->throw();
    }

    public function create(string $collection, string $id, array $data): bool
    {
        $response = $this->client()->post('/'.rawurlencode($collection).'?documentId='.rawurlencode($id), $this->fields($data));
        if ($response->status() === 409) return false;
        $response->throw();
        return true;
    }

    public function delete(string $collection, string $id): void
    {
        $response = $this->client()->delete($this->path($collection, $id));
        if ($response->status() !== 404) $response->throw();
    }

    public function page(string $collection, ?string $cursor = null, int $limit = 25): array
    {
        $data = $this->client()->get('/'.rawurlencode($collection), array_filter([
            'pageSize' => $limit, 'pageToken' => $cursor,
            // Record timestamps use Firestore's automatic descending field index.
            // Content has stable IDs and no timestamp; use its built-in name order.
            'orderBy' => in_array($collection, ['applications', 'visits', 'admins'], true)
                ? 'created_at desc' : '__name__ asc',
        ]))->throw()->json();
        return ['items' => array_map($this->document(...), $data['documents'] ?? []), 'next' => $data['nextPageToken'] ?? null];
    }

    public function count(string $collection): int
    {
        $data = $this->client()->post($this->endpoint().':runAggregationQuery', ['structuredAggregationQuery' => [
            'structuredQuery' => ['from' => [['collectionId' => $collection]]],
            'aggregations' => [['alias' => 'total', 'count' => (object) []]],
        ]])->throw()->json();
        return (int) ($data[0]['result']['aggregateFields']['total']['integerValue'] ?? 0);
    }
}
