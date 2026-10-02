<?php

namespace App\Services;

use App\Contracts\DocumentStore;
use Illuminate\Support\Facades\DB;

class SqliteDocumentStore implements DocumentStore
{
    private function query(string $collection)
    {
        return DB::table('documents')->where('collection', $collection);
    }

    public function get(string $collection, string $id): ?array
    {
        $row = $this->query($collection)->where('document_id', $id)->first();
        return $row ? ['id' => $id] + json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR) : null;
    }

    public function put(string $collection, string $id, array $data): void
    {
        unset($data['id']);
        DB::table('documents')->updateOrInsert(['collection' => $collection, 'document_id' => $id], [
            'payload' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function create(string $collection, string $id, array $data): bool
    {
        unset($data['id']);
        return (bool) DB::table('documents')->insertOrIgnore([
            'collection' => $collection, 'document_id' => $id,
            'payload' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function delete(string $collection, string $id): void
    {
        $this->query($collection)->where('document_id', $id)->delete();
    }

    public function page(string $collection, ?string $cursor = null, int $limit = 25): array
    {
        $rows = $this->query($collection)->when($cursor, fn ($q) => $q->where('document_id', '<', $cursor))
            ->orderByDesc('document_id')->limit($limit + 1)->get();
        $items = $rows->take($limit)->map(fn ($r) => ['id' => $r->document_id] + json_decode($r->payload, true))->all();
        return ['items' => $items, 'next' => $rows->count() > $limit ? end($items)['id'] : null];
    }

    public function count(string $collection): int
    {
        return $this->query($collection)->count();
    }
}
