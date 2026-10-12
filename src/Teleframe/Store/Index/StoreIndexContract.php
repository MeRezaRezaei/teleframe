<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index;

/**
 * TStore truth tables (verbatim S5/S3, plan W3): the local projection of
 * Teleframe's managed buckets, objects, and indexed media. One
 * implementation is Eloquent-backed (the shipped truth tables), one is a
 * memory array (tests/standalone). Every row carries account_id/bucket
 * explicitly — no global account scope here, the caller names the tenant.
 *
 * Row shapes (array form, DB column names as keys):
 * - bucket:  account_id, bucket, kind(store|data|backup), channel_id,
 *            access_hash, created_at_unix
 * - object:  account_id, bucket, name, mode(sealed|raw), size, chunks,
 *            metadata, created_at_unix, updated_at_unix
 * - media:   account_id, bucket, msg_id, media_type, doc_id, access_hash,
 *            file_reference, size, mime, content_sha256?, indexed_at_unix
 */
interface StoreIndexContract
{
    // ── tf_store_buckets (S5 local truth) ────────────────────────────

    /** @param  array<string, mixed>  $row */
    public function upsertBucket(int $accountId, string $bucket, array $row): void;

    /** @return array<string, mixed>|null */
    public function findBucket(int $accountId, string $bucket): ?array;

    /** @return list<array<string, mixed>> */
    public function listBuckets(int $accountId, ?string $kind = null): array;

    public function deleteBucket(int $accountId, string $bucket): void;

    // ── tf_store_objects (S2/S4 object index) ────────────────────────

    /** @param  array<string, mixed>  $row */
    public function upsertObject(int $accountId, string $bucket, string $name, array $row): void;

    /** @return array<string, mixed>|null */
    public function findObject(int $accountId, string $bucket, string $name): ?array;

    /** @return list<array<string, mixed>> */
    public function listObjects(int $accountId, string $bucket): array;

    public function deleteObject(int $accountId, string $bucket, string $name): void;

    // ── tf_store_media (S3 media hash index) ─────────────────────────

    /** @param  array<string, mixed>  $row */
    public function upsertMedia(int $accountId, string $bucket, int $msgId, array $row): void;

    /** @return array<string, mixed>|null */
    public function findMedia(int $accountId, string $bucket, int $msgId): ?array;

    /** @return list<array<string, mixed>> */
    public function listMedia(int $accountId, string $bucket): array;

    public function deleteMedia(int $accountId, string $bucket, int $msgId): void;
}
