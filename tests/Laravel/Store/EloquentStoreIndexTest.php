<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Store;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Store\Index\EloquentStoreIndex;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * EloquentStoreIndex (plan W3): the shipped truth tables over sqlite
 * (the same in-memory connection the Laravel tests use; Postgres runs
 * these via the Pg track). Covers upsert idempotence on the natural key,
 * account-isolated list/find, JSON blob roundtrips (chunks/metadata),
 * and bucket cascade dropping children through the DB FK.
 */
final class EloquentStoreIndexTest extends TestbenchTestCase
{
    private EloquentStoreIndex $index;

    /**
     * The tenant these fixtures write under: [2].
     *
     * MEASURED, not assumed: the only account_id literal in this file.
     *
     * This class opts into foreign-key enforcement itself
     * ('foreign_key_constraints' => true) and every mirror table carries
     * account_id -> telegram_accounts, so each insert was refused AT THE
     * DATABASE:
     *
     *   SQLSTATE[23000]: 19 FOREIGN KEY constraint failed
     *   insert into "tf_users" ("account_id", "id", "constructor") values (42, 101, user)
     *
     * It cannot extend IngestTestCase (Orchestra TestbenchTestCase, and the
     * mirror surface is migrated per-test here), so it plants its own tenant
     * in setUp(). Synthetic values only — no session string, no access hash.
     */
    protected function seedTenantRow(): void
    {
        foreach ([1, 2, 3, 5, 7] as $id) {
            if (DB::table('telegram_accounts')->where('user_id', $id)->exists()) {
                continue;
            }

            DB::table('telegram_accounts')->insert([
                'user_id' => $id,
                'label'  => 'laravel-fixture-'.$id,
                'type'   => 'user',
                'dc_id'  => 2,
            ]);
        }
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 3);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);
        $this->seedTenantRow();
        $this->index = new EloquentStoreIndex;
    }

    public function test_bucket_upsert_is_idempotent_on_natural_key(): void
    {
        $this->index->upsertBucket(1, 'media-pool', ['kind' => 'store', 'channel_id' => 101, 'access_hash' => 202, 'created_at_unix' => 1000]);
        $this->index->upsertBucket(1, 'media-pool', ['kind' => 'store', 'channel_id' => 303, 'access_hash' => 404, 'created_at_unix' => 1000]);

        $rows = $this->index->listBuckets(1);
        $this->assertCount(1, $rows, 'natural-key upsert must not duplicate');
        $this->assertSame(303, $rows[0]['channel_id'], 'latest upsert wins');
    }

    public function test_objects_roundtrip_json_columns_and_are_account_isolated(): void
    {
        $this->index->upsertBucket(7, 'media-pool', ['kind' => 'store', 'channel_id' => 1, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertBucket(1, 'media-pool', ['kind' => 'store', 'channel_id' => 5, 'access_hash' => 0, 'created_at_unix' => 0]);
        $chunks = [['n' => 0, 'offset' => 0, 'size' => 1024, 'sha256' => 'ab'.str_repeat('1', 62)]];
        $metadata = ['name' => 'docs/q3.zip', 'mode' => 'sealed', 'size' => 1024];

        $this->index->upsertObject(7, 'media-pool', 'docs/q3.zip', [
            'mode' => 'sealed', 'size' => 1024, 'chunks' => $chunks, 'metadata' => $metadata,
            'created_at_unix' => 10, 'updated_at_unix' => 11,
        ]);
        $this->index->upsertObject(1, 'media-pool', 'docs/q3.zip', [
            'mode' => 'raw', 'size' => 1, 'chunks' => [], 'metadata' => [],
            'created_at_unix' => 1, 'updated_at_unix' => 1,
        ]);

        $row = $this->index->findObject(7, 'media-pool', 'docs/q3.zip');
        $this->assertSame('sealed', $row['mode']);
        $this->assertSame($chunks, $row['chunks'], 'JSON chunks column must roundtrip as array');
        $this->assertSame($metadata, $row['metadata']);
        $this->assertSame('raw', $this->index->findObject(1, 'media-pool', 'docs/q3.zip')['mode']);
    }

    public function test_media_upsert_and_select_by_msg_id(): void
    {
        $this->index->upsertBucket(3, 'data-x', ['kind' => 'data', 'channel_id' => 9, 'access_hash' => 0, 'created_at_unix' => 0]);
        $this->index->upsertMedia(3, 'data-x', 501, [
            'media_type' => 'document', 'doc_id' => 9001, 'access_hash' => 55,
            'file_reference' => 'ref-1', 'size' => 512, 'mime' => 'application/zip',
            'content_sha256' => null, 'indexed_at_unix' => 10,
        ]);

        $row = $this->index->findMedia(3, 'data-x', 501);
        $this->assertSame(9001, $row['doc_id']);
        $this->assertSame('application/zip', $row['mime']);

        $rows = $this->index->listMedia(3, 'data-x');
        $this->assertCount(1, $rows);
        $this->assertSame('ref-1', $rows[0]['file_reference']);
    }

    public function test_delete_bucket_cascades_through_db_fk(): void
    {
        DB::table('tf_store_buckets')->insert([
            'account_id' => 2, 'bucket' => 'gone', 'kind' => 'store',
            'channel_id' => 5, 'access_hash' => 0, 'created_at_unix' => 0,
        ]);
        DB::table('tf_store_objects')->insert([
            'account_id' => 2, 'bucket' => 'gone', 'name' => 'a.txt', 'mode' => 'raw',
            'size' => 1, 'chunks' => '[]', 'metadata' => '{}',
            'created_at_unix' => 0, 'updated_at_unix' => 0,
        ]);

        $this->index->deleteBucket(2, 'gone');

        $this->assertSame([], $this->index->listObjects(2, 'gone'), 'cascade must drop children');
        $this->assertNull($this->index->findBucket(2, 'gone'));
    }
}
