<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index;

use Illuminate\Support\Facades\DB;

/**
 * Eloquent StoreIndexContract (plan W3): the shipped truth tables
 * (tf_store_buckets / tf_store_objects / tf_store_media, migration
 * 2026_09_20_000100). All writes go through the query builder's
 * natural-key upsert (updateOrInsert) — the tables carry composite keys
 * (account_id + bucket[+ name/msg_id]), which Eloquent's Model
 * save()/updateOrCreate() cannot target without a scalar PK. Rows cross
 * the boundary as arrays keyed by the DB column names; the JSON columns
 * (chunks, metadata) are encoded/decoded here. Re-syncs are idempotent;
 * FK cascades delete children with their bucket.
 */
final class EloquentStoreIndex implements StoreIndexContract
{
    public function upsertBucket(int $accountId, string $bucket, array $row): void
    {
        DB::table('tf_store_buckets')->updateOrInsert(
            ['account_id' => $accountId, 'bucket' => $bucket],
            $row,
        );
    }

    public function findBucket(int $accountId, string $bucket): ?array
    {
        $row = DB::table('tf_store_buckets')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function listBuckets(int $accountId, ?string $kind = null): array
    {
        $query = DB::table('tf_store_buckets')->where('account_id', $accountId);
        if ($kind !== null) {
            $query->where('kind', $kind);
        }

        return array_map('get_object_vars', $query->orderBy('bucket')->get()->all());
    }

    public function deleteBucket(int $accountId, string $bucket): void
    {
        DB::table('tf_store_buckets')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->delete();
    }

    public function upsertObject(int $accountId, string $bucket, string $name, array $row): void
    {
        $row['chunks'] = json_encode($row['chunks'] ?? [], JSON_THROW_ON_ERROR);
        $row['metadata'] = json_encode($row['metadata'] ?? [], JSON_THROW_ON_ERROR);
        DB::table('tf_store_objects')->updateOrInsert(
            ['account_id' => $accountId, 'bucket' => $bucket, 'name' => $name],
            $row,
        );
    }

    public function findObject(int $accountId, string $bucket, string $name): ?array
    {
        $row = DB::table('tf_store_objects')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->where('name', $name)
            ->first();

        if ($row === null) {
            return null;
        }
        $row = (array) $row;
        $row['chunks'] = json_decode((string) $row['chunks'], true, 512, JSON_THROW_ON_ERROR);
        $row['metadata'] = json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR);

        return $row;
    }

    public function listObjects(int $accountId, string $bucket): array
    {
        $rows = DB::table('tf_store_objects')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->orderBy('name')
            ->get()
            ->all();

        return array_map(function (object $row): array {
            $row = (array) $row;
            $row['chunks'] = json_decode((string) $row['chunks'], true, 512, JSON_THROW_ON_ERROR);
            $row['metadata'] = json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR);

            return $row;
        }, $rows);
    }

    public function deleteObject(int $accountId, string $bucket, string $name): void
    {
        DB::table('tf_store_objects')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->where('name', $name)
            ->delete();
    }

    public function upsertMedia(int $accountId, string $bucket, int $msgId, array $row): void
    {
        DB::table('tf_store_media')->updateOrInsert(
            ['account_id' => $accountId, 'bucket' => $bucket, 'msg_id' => $msgId],
            $row,
        );
    }

    public function findMedia(int $accountId, string $bucket, int $msgId): ?array
    {
        $row = DB::table('tf_store_media')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->where('msg_id', $msgId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function listMedia(int $accountId, string $bucket): array
    {
        return array_map('get_object_vars', DB::table('tf_store_media')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->orderBy('msg_id')
            ->get()
            ->all());
    }

    public function deleteMedia(int $accountId, string $bucket, int $msgId): void
    {
        DB::table('tf_store_media')
            ->where('account_id', $accountId)
            ->where('bucket', $bucket)
            ->where('msg_id', $msgId)
            ->delete();
    }
}
