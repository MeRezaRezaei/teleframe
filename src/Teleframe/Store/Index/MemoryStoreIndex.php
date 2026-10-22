<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index;

/**
 * In-memory StoreIndexContract (plan W3): nested arrays keyed by account →
 * bucket → name/msg_id. Used by tests, the standalone smoke, and any
 * sandbox where no database surface exists. Upserts replace whole rows.
 */
final class MemoryStoreIndex implements StoreIndexContract
{
    /** @var array<int, array<string, array<string, mixed>>> account → bucket → row */
    private array $buckets = [];

    /** @var array<int, array<string, array<string, array<string, mixed>>>> account → bucket → name → row */
    private array $objects = [];

    /** @var array<int, array<string, array<int, array<string, mixed>>>> account → bucket → msg_id → row */
    private array $media = [];

    public function upsertBucket(int $accountId, string $bucket, array $row): void
    {
        $row['account_id'] = $accountId;
        $row['bucket'] = $bucket;
        $this->buckets[$accountId][$bucket] = $row;
    }

    public function findBucket(int $accountId, string $bucket): ?array
    {
        return $this->buckets[$accountId][$bucket] ?? null;
    }

    public function listBuckets(int $accountId, ?string $kind = null): array
    {
        $rows = $this->buckets[$accountId] ?? [];
        if ($kind === null) {
            return array_values($rows);
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => ($row['kind'] ?? null) === $kind,
        ));
    }

    public function deleteBucket(int $accountId, string $bucket): void
    {
        unset($this->buckets[$accountId][$bucket]);
        unset($this->objects[$accountId][$bucket]);
        unset($this->media[$accountId][$bucket]);
    }

    public function upsertObject(int $accountId, string $bucket, string $name, array $row): void
    {
        $row['account_id'] = $accountId;
        $row['bucket'] = $bucket;
        $row['name'] = $name;
        $this->objects[$accountId][$bucket][$name] = $row;
    }

    public function findObject(int $accountId, string $bucket, string $name): ?array
    {
        return $this->objects[$accountId][$bucket][$name] ?? null;
    }

    public function listObjects(int $accountId, string $bucket): array
    {
        return array_values($this->objects[$accountId][$bucket] ?? []);
    }

    public function deleteObject(int $accountId, string $bucket, string $name): void
    {
        unset($this->objects[$accountId][$bucket][$name]);
    }

    public function upsertMedia(int $accountId, string $bucket, int $msgId, array $row): void
    {
        $row['account_id'] = $accountId;
        $row['bucket'] = $bucket;
        $row['msg_id'] = $msgId;
        $this->media[$accountId][$bucket][$msgId] = $row;
    }

    public function findMedia(int $accountId, string $bucket, int $msgId): ?array
    {
        return $this->media[$accountId][$bucket][$msgId] ?? null;
    }

    public function listMedia(int $accountId, string $bucket): array
    {
        return array_values($this->media[$accountId][$bucket] ?? []);
    }

    public function deleteMedia(int $accountId, string $bucket, int $msgId): void
    {
        unset($this->media[$accountId][$bucket][$msgId]);
    }
}
