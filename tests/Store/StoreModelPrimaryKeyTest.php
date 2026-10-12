<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreBucket;
use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreMedia;
use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The three composite-key store truth models through REAL Eloquent
 * (sqlite in-memory, schema produced by EXECUTING the shipped migration
 * 2026_09_20_000100 — composite $table->primary([...]) on all three
 * tables, plus the (account_id, bucket) foreign key).
 *
 * These models used to declare $incrementing = false with NO $primaryKey,
 * so Eloquent resolved a key named `id` — a column that does not exist in
 * any of the three tables. They survived only because EloquentStoreIndex
 * bypasses Eloquent with raw DB::table(), so the bug was invisible. This
 * suite exercises the Eloquent path the brief cares about: a real
 * insert/getKey()/update through a model, a foreign-key round-trip between
 * a TfStoreObject and its TfStoreBucket, and the update-isolation that the
 * composite-key guard exists to provide.
 *
 * Provenance: migration 2026_09_20_000100_create_tf_store_tables.php.
 */
final class StoreModelPrimaryKeyTest extends TestCase
{
    private const ACCOUNT = 7;

    protected function tearDown(): void
    {
        self::assertFalse(Model::isUnguarded(), 'mass-assignment guard must be restored');

        parent::tearDown();
    }

    /**
     * Boot Eloquent on a real sqlite database carrying the shipped DDL.
     *
     * The schema is produced by EXECUTING
     * 2026_09_20_000100_create_tf_store_tables.php, never transcribed here:
     * a second hand-typed copy of the DDL is the disease, the derived schema
     * is the cure. Foreign-key enforcement is switched on here (sqlite
     * defaults it off) because these tests assert the composite FK is live.
     */
    private static function withStoreTables(callable $fn): void
    {
        $capsule = ShippedStoreSchema::capsule();
        $capsule->getConnection()->statement('PRAGMA foreign_keys = ON');

        $fn($capsule);
    }

    public function test_key_surrogate_matches_the_ddl_composite_key(): void
    {
        self::assertSame('account_id', (new TfStoreBucket)->getKeyName());
        self::assertSame('account_id', (new TfStoreObject)->getKeyName());
        self::assertSame('account_id', (new TfStoreMedia)->getKeyName());

        self::assertFalse((new TfStoreBucket)->getIncrementing());
        self::assertFalse((new TfStoreObject)->getIncrementing());
        self::assertFalse((new TfStoreMedia)->getIncrementing());
    }

    public function test_insert_and_read_back_through_eloquent(): void
    {
        self::withStoreTables(function (): void {
            TfStoreBucket::unguard();

            try {
                $bucket = new TfStoreBucket;
                $bucket->account_id = self::ACCOUNT;
                $bucket->bucket = 'media-pool';
                $bucket->kind = 'store';
                $bucket->channel_id = -1001234567890;
                $bucket->access_hash = 4242;
                $bucket->created_at_unix = 1700000000;
                $bucket->save();

                // The key Eloquent actually resolves must be a real column.
                self::assertSame(self::ACCOUNT, $bucket->getKey());
                self::assertTrue($bucket->exists);

                $fresh = TfStoreBucket::query()
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->firstOrFail();

                self::assertSame('media-pool', $fresh->bucket);
                self::assertSame('store', $fresh->kind);
                self::assertSame(-1001234567890, (int) $fresh->channel_id);
                self::assertSame(4242, (int) $fresh->access_hash);
            } finally {
                TfStoreBucket::reguard();
            }
        });
    }

    /**
     * The foreign-key round-trip the brief asks for: a TfStoreObject that
     * points at a TfStoreBucket, read back through Eloquent, plus proof the
     * FK is actually live (an orphan object is rejected, not silently kept).
     */
    public function test_object_round_trips_a_foreign_key_to_its_bucket(): void
    {
        self::withStoreTables(function (Capsule $capsule): void {
            TfStoreBucket::unguard();
            TfStoreObject::unguard();

            try {
                $bucket = new TfStoreBucket;
                $bucket->account_id = self::ACCOUNT;
                $bucket->bucket = 'media-pool';
                $bucket->kind = 'store';
                $bucket->channel_id = 1;
                $bucket->access_hash = 2;
                $bucket->created_at_unix = 1000;
                $bucket->save();

                $object = new TfStoreObject;
                $object->account_id = self::ACCOUNT;
                $object->bucket = 'media-pool'; // the FK half Eloquent must carry
                $object->name = 'dir/file.bin';
                $object->mode = 'sealed';
                $object->size = 12;
                $object->chunks = [['n' => 0, 'offset' => 0, 'size' => 12, 'sha256' => 'abc']];
                $object->metadata = ['ver' => 'TFSTORE1'];
                $object->created_at_unix = 1000;
                $object->updated_at_unix = 2000;
                $object->save();

                $fresh = TfStoreObject::query()
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->where('name', 'dir/file.bin')
                    ->firstOrFail();

                self::assertSame('media-pool', $fresh->bucket);
                self::assertSame(12, (int) $fresh->size);
                self::assertSame([['n' => 0, 'offset' => 0, 'size' => 12, 'sha256' => 'abc']], $fresh->chunks);
                self::assertSame(['ver' => 'TFSTORE1'], $fresh->metadata);

                // The joined parent really is there — read through Eloquent both sides.
                $parent = TfStoreBucket::query()
                    ->where('account_id', $fresh->account_id)
                    ->where('bucket', $fresh->bucket)
                    ->firstOrFail();

                self::assertSame('media-pool', $parent->bucket);

                // FK is live: an object for a bucket that does not exist is rejected.
                $orphan = new TfStoreObject;
                $orphan->account_id = self::ACCOUNT;
                $orphan->bucket = 'no-such-bucket';
                $orphan->name = 'orphan.bin';
                $orphan->mode = 'sealed';
                $orphan->size = 1;
                $orphan->chunks = [];
                $orphan->metadata = [];
                $orphan->created_at_unix = 1000;
                $orphan->updated_at_unix = 1000;

                $rejected = false;

                try {
                    $orphan->save();
                } catch (RuntimeException) {
                    $rejected = true;
                }

                self::assertTrue($rejected, 'foreign key must reject an object with no bucket');

                $capsule->getConnection()->table('tf_store_buckets')
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->delete();

                self::assertSame(0, TfStoreObject::query()->count(), 'bucket delete must cascade to its objects');
            } finally {
                TfStoreObject::reguard();
                TfStoreBucket::reguard();
            }
        });
    }

    /**
     * The reason the composite-key guard exists: saving a LOADED row must
     * touch only that row. With a single-column $primaryKey surrogate
     * (account_id) and no guard, this update would rewrite every object in
     * the account.
     */
    public function test_saving_a_loaded_row_updates_only_that_row(): void
    {
        self::withStoreTables(function (): void {
            TfStoreBucket::unguard();
            TfStoreObject::unguard();

            try {
                $bucket = new TfStoreBucket;
                $bucket->account_id = self::ACCOUNT;
                $bucket->bucket = 'media-pool';
                $bucket->kind = 'store';
                $bucket->channel_id = 1;
                $bucket->access_hash = 2;
                $bucket->created_at_unix = 1000;
                $bucket->save();

                foreach ([['a.bin', 10], ['b.bin', 20]] as [$name, $size]) {
                    $object = new TfStoreObject;
                    $object->account_id = self::ACCOUNT;
                    $object->bucket = 'media-pool';
                    $object->name = $name;
                    $object->mode = 'sealed';
                    $object->size = $size;
                    $object->chunks = [];
                    $object->metadata = [];
                    $object->created_at_unix = 1000;
                    $object->updated_at_unix = 1000;
                    $object->save();
                }

                $loaded = TfStoreObject::query()
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->where('name', 'a.bin')
                    ->firstOrFail();

                $loaded->size = 999;
                $loaded->save();

                $sizes = TfStoreObject::query()
                    ->orderBy('name')
                    ->pluck('size')
                    ->all();

                self::assertSame([999, 20], array_map('intval', $sizes), 'only the loaded row may change');
            } finally {
                TfStoreObject::reguard();
                TfStoreBucket::reguard();
            }
        });
    }

    /**
     * tf_store_media shares the same composite key shape and the same
     * all-columns-NOT-NULL DDL; exercise its Eloquent path too, including a
     * cast round-trip on the Telegram-native identity columns.
     */
    public function test_media_insert_read_back_and_update_in_eloquent(): void
    {
        self::withStoreTables(function (): void {
            TfStoreBucket::unguard();
            TfStoreMedia::unguard();

            try {
                $bucket = new TfStoreBucket;
                $bucket->account_id = self::ACCOUNT;
                $bucket->bucket = 'media-pool';
                $bucket->kind = 'data';
                $bucket->channel_id = 1;
                $bucket->access_hash = 2;
                $bucket->created_at_unix = 1000;
                $bucket->save();

                $media = new TfStoreMedia;
                $media->account_id = self::ACCOUNT;
                $media->bucket = 'media-pool';
                $media->msg_id = 55;
                $media->media_type = 'document';
                $media->doc_id = 777;
                $media->access_hash = -888;
                $media->file_reference = 'AQADAgAD';
                $media->size = 4096;
                $media->mime = 'image/jpeg';
                $media->content_sha256 = null;
                $media->indexed_at_unix = 1700000000;
                $media->save();

                self::assertSame(self::ACCOUNT, $media->getKey());

                $fresh = TfStoreMedia::query()
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->where('msg_id', 55)
                    ->firstOrFail();

                self::assertSame(55, (int) $fresh->msg_id);
                self::assertSame(777, (int) $fresh->doc_id);
                self::assertSame(-888, (int) $fresh->access_hash);
                self::assertSame(4096, (int) $fresh->size);
                self::assertNull($fresh->content_sha256);

                $fresh->content_sha256 = str_repeat('a', 64);
                $fresh->save();

                $again = TfStoreMedia::query()
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->where('msg_id', 55)
                    ->firstOrFail();

                self::assertSame(str_repeat('a', 64), $again->content_sha256);
            } finally {
                TfStoreMedia::reguard();
                TfStoreBucket::reguard();
            }
        });
    }

    /**
     * delete() rides the same setKeysForSaveQuery path as save(); assert it
     * removes one natural-key row, not the whole account.
     */
    public function test_delete_is_scoped_to_the_natural_key(): void
    {
        self::withStoreTables(function (): void {
            TfStoreBucket::unguard();
            TfStoreObject::unguard();

            try {
                $bucket = new TfStoreBucket;
                $bucket->account_id = self::ACCOUNT;
                $bucket->bucket = 'media-pool';
                $bucket->kind = 'store';
                $bucket->channel_id = 1;
                $bucket->access_hash = 2;
                $bucket->created_at_unix = 1000;
                $bucket->save();

                foreach (['a.bin', 'b.bin'] as $name) {
                    $object = new TfStoreObject;
                    $object->account_id = self::ACCOUNT;
                    $object->bucket = 'media-pool';
                    $object->name = $name;
                    $object->mode = 'sealed';
                    $object->size = 1;
                    $object->chunks = [];
                    $object->metadata = [];
                    $object->created_at_unix = 1000;
                    $object->updated_at_unix = 1000;
                    $object->save();
                }

                $doomed = TfStoreObject::query()
                    ->where('account_id', self::ACCOUNT)
                    ->where('bucket', 'media-pool')
                    ->where('name', 'a.bin')
                    ->firstOrFail();

                self::assertTrue($doomed->delete());
                self::assertSame(1, TfStoreObject::query()->count());
                self::assertSame(1, TfStoreBucket::query()->count(), 'the bucket must survive its object delete');
            } finally {
                TfStoreObject::reguard();
                TfStoreBucket::reguard();
            }
        });
    }
}
