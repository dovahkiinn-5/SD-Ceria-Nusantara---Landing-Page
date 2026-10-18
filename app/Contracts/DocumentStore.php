<?php

namespace App\Contracts;

interface DocumentStore
{
    public function get(string $collection, string $id): ?array;
    /** @return array<string, array|null> Documents keyed by ID. */
    public function getMany(string $collection, array $ids): array;
    public function put(string $collection, string $id, array $data): void;
    public function create(string $collection, string $id, array $data): bool;
    public function delete(string $collection, string $id): void;
    public function page(string $collection, ?string $cursor = null, int $limit = 25): array;
    public function count(string $collection): int;
}
