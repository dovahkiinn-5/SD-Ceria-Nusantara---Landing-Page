<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Illuminate\Support\Facades\Http;

class FirestoreDocumentStore implements DocumentStore
{
    private function endpoint(): string
    {
        return 'https://firestore.googleapis.com/v1/projects/'.rawurlencode(config('school.firebase_project')).'/databases/'.rawurlencode(config('school.firebase_database')).'/documents';
    }
    private function client()
    {
        return Http::withToken($this->accessToken())->acceptJson()->withOptions((new FirebaseCredentials)->httpOptions())
            ->baseUrl($this->endpoint());
    }

    protected function accessToken(): string
    {
        return (new FirebaseCredentials)->accessToken('https://www.googleapis.com/auth/datastore');
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

    public function putMany(string $collection, array $documents): void
    {
        $this->putDocuments([$collection => $documents]);
    }

    public function deleteMany(string $collection, array $ids): void
    {
        $this->deleteDocuments([$collection => $ids]);
    }

    public function putDocuments(array $collections): void
    {
        $writes = [];
        foreach ($collections as $collection => $documents) {
            foreach ($documents as $id => $data) {
                $writes[] = ['update' => ['name' => $this->resourceName($collection, (string) $id)] + $this->fields($data)];
            }
        }

        if (! $this->commitWrites($writes)) {
            throw new \RuntimeException('Firestore menolak batch penulisan dokumen.');
        }
    }

    public function deleteDocuments(array $collections): void
    {
        $writes = [];
        foreach ($collections as $collection => $ids) {
            foreach ($ids as $id) {
                $writes[] = ['delete' => $this->resourceName($collection, (string) $id)];
            }
        }

        if (! $this->commitWrites($writes)) {
            throw new \RuntimeException('Firestore menolak batch penghapusan dokumen.');
        }
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

    /** Read the server update time alongside data for optimistic concurrency. */
    public function snapshot(string $collection, string $id): array
    {
        $response = $this->client()->get($this->path($collection, $id));
        if ($response->status() === 404) return ['data'=>null, 'version'=>null];
        $document = $response->throw()->json();
        return ['data'=>$this->document($document), 'version'=>$document['updateTime']];
    }

    /** Only commit when the document still has the version observed by the caller. */
    public function compareAndSwap(string $collection, string $id, ?string $version, array $data): bool
    {
        return $this->conditionalCommit([
            'update'=>['name'=>$this->resourceName($collection, $id)] + $this->fields($data),
            'currentDocument'=>$version === null ? ['exists'=>false] : ['updateTime'=>$version],
        ]);
    }

    public function deleteIfUnchanged(string $collection, string $id, string $version): bool
    {
        return $this->conditionalCommit(['delete'=>$this->resourceName($collection, $id),
            'currentDocument'=>['updateTime'=>$version]]);
    }

    private function resourceName(string $collection, string $id): string
    {
        return 'projects/'.config('school.firebase_project').'/databases/'.config('school.firebase_database').'/documents/'.$collection.'/'.$id;
    }

    private function conditionalCommit(array $write): bool
    {
        return $this->commitWrites([$write]);
    }

    private function commitWrites(array $writes): bool
    {
        if ($writes === []) {
            return true;
        }
        if (count($writes) > 500) {
            throw new \InvalidArgumentException('Firestore batch maksimal 500 dokumen.');
        }

        $response = $this->client()->post($this->endpoint().':commit', ['writes' => $writes]);
        if (in_array($response->json('error.status'), ['FAILED_PRECONDITION', 'ALREADY_EXISTS', 'ABORTED', 'NOT_FOUND'], true)) return false;
        $response->throw();
        return true;
    }

    /** Bounded sweep; update-time preconditions prevent deleting renewed records. */
    public function expiredSnapshots(string $collection, int $timestamp, int $limit = 100): array
    {
        $response = $this->client()->post($this->endpoint().':runQuery', ['structuredQuery'=>[
            'from'=>[['collectionId'=>$collection]],
            'where'=>['compositeFilter'=>['op'=>'AND', 'filters'=>[
                ['fieldFilter'=>['field'=>['fieldPath'=>'expires_at'], 'op'=>'GREATER_THAN', 'value'=>['integerValue'=>'0']]],
                ['fieldFilter'=>['field'=>['fieldPath'=>'expires_at'], 'op'=>'LESS_THAN_OR_EQUAL', 'value'=>['integerValue'=>(string) $timestamp]]],
            ]]],
            'orderBy'=>[['field'=>['fieldPath'=>'expires_at'], 'direction'=>'ASCENDING']],
            'limit'=>max(1, min(100, $limit)),
        ]])->throw()->json();
        $items = [];
        foreach ($response as $row) {
            if (isset($row['document'])) $items[] = ['data'=>$this->document($row['document']), 'version'=>$row['document']['updateTime']];
        }
        return $items;
    }
}
