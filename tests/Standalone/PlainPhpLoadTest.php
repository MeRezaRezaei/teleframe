<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * Mirrors bin/standalone-smoke.php: every merged module must be loadable and
 * constructible with NO Laravel app booted. Before the Phase 1 fix this
 * fatals: UpdateStored imported Illuminate\Foundation\Events\Dispatchable
 * and Illuminate\Queue\SerializesModels — neither is a production dependency.
 */
final class PlainPhpLoadTest extends TestCase
{
    public function test_update_stored_loads_without_laravel(): void
    {
        self::assertTrue(class_exists(UpdateStored::class));
    }

    public function test_update_stored_has_no_framework_traits(): void
    {
        $uses = class_uses(UpdateStored::class);

        self::assertSame([], $uses);
    }

    public function test_ingestor_constructs_without_laravel(): void
    {
        $ingestor = new UpdateIngestor();

        self::assertInstanceOf(UpdateIngestor::class, $ingestor);
    }

    public function test_public_face_constructs_from_ingest_singletons(): void
    {
        $teleclient = new Teleclient(new UpdateIngestor(), new EntityAggregator());

        self::assertInstanceOf(Teleclient::class, $teleclient);
    }

    public function test_bus_stream_schema_codec_round_trips(): void
    {
        $entry = ['account_id' => 42, 'update' => ['_' => 'updateNewMessage', 'seq' => 7], 'ts' => 1720000000];

        self::assertSame($entry, StreamSchema::decode(StreamSchema::encode($entry)));
    }

    public function test_bus_sink_and_route_table_bind_to_the_memory_double(): void
    {
        $redis = new ArrayRedis();

        self::assertInstanceOf(RouteTable::class, new RouteTable($redis));
        self::assertInstanceOf(RedisStreamSink::class, new RedisStreamSink($redis, 42));
    }

    public function test_backfill_worker_constructs_without_a_fetch_source(): void
    {
        $worker = new BackfillWorker(10, 3600, static fn (int $seconds): bool => true);

        self::assertInstanceOf(BackfillWorker::class, $worker);
    }

    public function test_backup_memory_vault_constructs(): void
    {
        self::assertInstanceOf(InMemoryVault::class, new InMemoryVault());
    }

    public function test_schema_loader_parses_without_config(): void
    {
        $scheme = TeleframeSchemeLoader::parseString('bool#0c734172 = Bool;');

        self::assertNotSame([], $scheme->types());
    }

    public function test_schema_regenerator_loads_the_committed_sources(): void
    {
        $scheme = (new SchemaRegenerator())->loadScheme(dirname(__DIR__, 2) . '/schema/sources');

        self::assertNotSame([], $scheme->types());
    }

    public function test_daemon_account_worker_constructs_with_injected_factories(): void
    {
        $worker = new AccountWorker(
            ['account_id' => 1, 'flood_cap_seconds' => 3600],
            static function (): void {
                throw new \RuntimeException('scope factory invoked — test never touches the wire');
            },
            null,
            static fn (int $seconds): bool => true,
        );

        self::assertInstanceOf(AccountWorker::class, $worker);
    }
}