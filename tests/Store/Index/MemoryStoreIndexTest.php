<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Index;

use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use PHPUnit\Framework\TestCase;

/**
 * MemoryStoreIndex (plan W3): the in-memory StoreIndexContract used by
 * tests/standalone. Covers upsert-overwrite semantics, find/list with
 * kind filter, account isolation, and cascading delete (bucket removal
 * drops its objects and media).
 */
final class MemoryStoreIndexTest extends TestCase
{
    private MemoryStoreIndex $index;

    protected function setUp(): void
    {
        $this->index = new MemoryStoreIndex;
    }

    public function test_bucket_upsert_overwrites_and_finds(): void
    {
        $this->index->upsertBucket(1, 'media-pool', [
            'kind' => 'store', 'channel_id' => 101, 'access_hash' => 202, 'created_at_unix' => 1000,
        ]);
        $row = $this->index->findBucket(1, 'media-pool');
        $this->assertSame('store', $row['kind']);
        $this->assertSame(101, $row['channel_id']);
        $this->assertSame('media-pool', $row['bucket']);
        $this->assertSame(1, $row['account_id']);

        $this->index->upsertBucket(1, 'media-pool', [
            'kind' => 'data', 'channel_id' => 999, 'access_hash' => 0, 'created_at_unix' => 1001,
        ]);
        $this->assertSame('data', $this->index->findBucket(1, 'media-pool')['kind'], 'upsert must overwrite');
    }

    public function test_list_buckets_filters_by_kind_and_account(): void
    {
        $this->index->upsertBucket(1, 'a-store', ['kind' => 'store', 'channel_id' => 1, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertBucket(1, 'b-data', ['kind' => 'data', 'channel_id' => 2, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertBucket(2, 'c-backup', ['kind' => 'backup', 'channel_id' => 3, 'access_hash' => 0, 'created_at_unix' => 0]);

        $this->assertCount(2, $this->index->listBuckets(1));
        $this->assertCount(1, $this->index->listBuckets(1, 'store'));
        $this->assertCount(1, $this->index->listBuckets(1, 'data'));
        $this->assertCount(0, $this->index->listBuckets(1, 'backup'), 'account 2 backup must not leak');
    }

    public function test_object_upsert_find_list_delete_ranking_by_name(): void
    {
        $this->index->upsertBucket(1, 'media-pool', ['kind' => 'store', 'channel_id' => 1, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertObject(1, 'media-pool', 'a.txt', ['mode' => 'raw', 'size' => 10, 'chunks' => [], 'metadata' => ['name' => 'a'], 'created_at_unix' => 1, 'updated_at_unix' => 1]);
        $this->index->upsertObject(1, 'media-pool', 'b.txt', ['mode' => 'sealed', 'size' => 20, 'chunks' => [['n' => 0, 'offset' => 0]], 'metadata' => ['name' => 'b'], 'created_at_unix' => 2, 'updated_at_unix' => 2]);

        $rows = $this->index->listObjects(1, 'media-pool');
        $this->assertCount(2, $rows);
        $this->assertSame(['a.txt', 'b.txt'], array_column($rows, 'name'), 'list ordered by name');

        $found = $this->index->findObject(1, 'media-pool', 'b.txt');
        $this->assertSame('sealed', $found['mode']);
        $this->assertSame(20, $found['size']);
        $this->assertSame([['n' => 0, 'offset' => 0]], $found['chunks']);
        $this->assertNull($this->index->findObject(2, 'media-pool', 'b.txt'), 'account isolation');

        $this->index->deleteObject(1, 'media-pool', 'b.txt');
        $this->assertNull($this->index->findObject(1, 'media-pool', 'b.txt'));
        $this->assertCount(1, $this->index->listObjects(1, 'media-pool'));
    }

    public function test_media_upsert_find_list_and_account_isolation(): void
    {
        $this->index->upsertBucket(1, 'data-x', ['kind' => 'data', 'channel_id' => 7, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertMedia(1, 'data-x', 501, [
            'media_type' => 'document', 'doc_id' => 9001, 'access_hash' => 55,
            'file_reference' => 'ref-1', 'size' => 512, 'mime' => 'image/png',
            'content_sha256' => null, 'indexed_at_unix' => 10,
        ]);

        $row = $this->index->findMedia(1, 'data-x', 501);
        $this->assertSame(9001, $row['doc_id']);
        $this->assertSame('image/png', $row['mime']);
        $this->assertNull($row['content_sha256']);
        $this->assertNull($this->index->findMedia(2, 'data-x', 501));

        $this->index->deleteMedia(1, 'data-x', 501);
        $this->assertNull($this->index->findMedia(1, 'data-x', 501));
    }

    public function test_delete_bucket_cascades_objects_and_media(): void
    {
        $this->index->upsertBucket(1, 'media-pool', ['kind' => 'store', 'channel_id' => 1, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertObject(1, 'media-pool', 'a.txt', ['mode' => 'raw', 'size' => 1, 'chunks' => [], 'metadata' => [], 'created_at_unix' => 0, 'updated_at_unix' => 0]);
        $this->index->upsertMedia(1, 'media-pool', 5, ['media_type' => 'document', 'doc_id' => 1, 'access_hash' => 0, 'file_reference' => '', 'size' => 1, 'mime' => '', 'content_sha256' => null, 'indexed_at_unix' => 0]);

        $this->index->deleteBucket(1, 'media-pool');

        $this->assertNull($this->index->findBucket(1, 'media-pool'));
        $this->assertSame([], $this->index->listObjects(1, 'media-pool'));
        $this->assertSame([], $this->index->listMedia(1, 'media-pool'));
    }
}
