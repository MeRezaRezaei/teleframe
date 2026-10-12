<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Store\Index\EloquentStoreIndex;

/**
 * TStore truth tables on Postgres (verbatim S5/S3, plan W3): the store
 * migration joins the shipped dial (UpdateIngestor::migrationPaths()
 * covers the whole src/Laravel/Migrations dir), so the Pg track proves the
 * tf_store_* tables migrate and the Eloquent index's natural-key upsert is
 * idempotent and account-isolated on real Postgres (SQLite runs the same
 * suite in the main matrix; this is the pg truth check).
 */
final class StoreTfTablesPgTest extends PgTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateDomainSet();
    }

    public function test_store_tables_migrate_up_on_postgres(): void
    {
        foreach (['tf_store_buckets', 'tf_store_objects', 'tf_store_media'] as $table) {
            self::assertTrue(DB::getSchemaBuilder()->hasTable($table), "store table {$table} exists");
        }
    }

    public function test_store_index_upsert_is_idempotent_and_account_isolated_on_pg(): void
    {
        $index = new EloquentStoreIndex;
        // Tenant rows for 7 and 2 — the ids arrive as METHOD ARGUMENTS here, never as
        // an 'account_id' => literal, so they cannot be found by scanning for one.
        $this->seedPgTenants(7, 2);

        $index->upsertBucket(7, 'media-pool', ['kind' => 'store', 'channel_id' => 101, 'access_hash' => 202, 'created_at_unix' => 1000]);
        $index->upsertBucket(7, 'media-pool', ['kind' => 'store', 'channel_id' => 303, 'access_hash' => 404, 'created_at_unix' => 1000]);
        $index->upsertBucket(2, 'media-pool', ['kind' => 'backup', 'channel_id' => 5, 'access_hash' => 0, 'created_at_unix' => 1000]);

        $rows = $index->listBuckets(7);
        self::assertCount(1, $rows, 'natural-key upsert must not duplicate');
        self::assertSame(303, $rows[0]['channel_id'], 'latest upsert wins');
        self::assertSame(['backup'], array_column($index->listBuckets(2), 'kind'), 'account isolation');

        $index->upsertObject(7, 'media-pool', 'docs/q3.zip', [
            'mode' => 'sealed', 'size' => 1024,
            'chunks' => [['n' => 0, 'offset' => 0, 'size' => 1024]],
            'metadata' => ['name' => 'docs/q3.zip', 'mode' => 'sealed'],
            'created_at_unix' => 10, 'updated_at_unix' => 11,
        ]);
        $row = $index->findObject(7, 'media-pool', 'docs/q3.zip');
        self::assertSame('sealed', $row['mode']);
        self::assertSame([['n' => 0, 'offset' => 0, 'size' => 1024]], $row['chunks'], 'JSON chunks roundtrip');
    }
}
