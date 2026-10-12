<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;

/**
 * `teleframe:tstore` — the StoreService CLI shell (plan W7).
 *
 * The container seams (six public keys) are bound to offline doubles: a
 * FakeStoreApi-backed channel factory, an in-memory index/cache/queue, so
 * every action runs without credentials or I/O. Command registration
 * mirrors the provider's runningInConsole list.
 */
final class TstoreCommandTest extends TestCase
{
    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FakeStoreApi(scope: 'cli');
        $this->index = new MemoryStoreIndex;

        $this->app->make(Kernel::class)->registerCommand(new TstoreCommand);

        $this->app->bind(TstoreCommand::CHANNEL_FACTORY_KEY, fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()));
        $this->app->instance(TstoreCommand::INDEX_KEY, $this->index);
        $this->app->instance(TstoreCommand::HOT_CACHE_KEY, new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024));
        $this->app->instance(TstoreCommand::QUEUE_KEY, new SyncUploadQueue);
        $this->app->instance(TstoreCommand::SCHEDULES_KEY, new NullSchedulePublisher);
        $this->app->instance(TstoreCommand::SERVICE_KEY, new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024),
            queue: new SyncUploadQueue,
        ));
    }

    public function test_unknown_action_returns_failure(): void
    {
        self::assertSame(TstoreCommand::FAILURE, Artisan::call('teleframe:tstore', ['action' => 'explode']));
        self::assertStringContainsString('unknown action', Artisan::output());
    }

    public function test_buckets_lists_registry_or_reports_empty(): void
    {
        Artisan::call('teleframe:tstore', ['action' => 'buckets']);
        self::assertStringContainsString('No buckets registered yet', Artisan::output());
    }

    public function test_put_and_get_roundtrip_through_the_cli(): void
    {
        $bytes = str_repeat('cli-payload-', 20_000);
        $tmp = tempnam(sys_get_temp_dir(), 'tfstore-');
        file_put_contents($tmp, $bytes);

        try {
            $exit = Artisan::call('teleframe:tstore', [
                'action' => 'put',
                'bucket' => 'media-pool',
                'name' => 'clip.bin',
                'source' => $tmp,
            ]);
            self::assertSame(TstoreCommand::SUCCESS, $exit);
            self::assertStringContainsString('Uploaded media-pool/clip.bin', Artisan::output());

            Artisan::call('teleframe:tstore', [
                'action' => 'get',
                'bucket' => 'media-pool',
                'name' => 'clip.bin',
            ]);
            self::assertSame($bytes, rtrim(Artisan::output(), "\n"));
        } finally {
            @unlink($tmp);
        }
    }

    public function test_sealed_put_get_via_passphrase_file_option(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tfstore-');
        file_put_contents($tmp, 'sealed cli payload');

        // There is deliberately NO --passphrase option: argv is world-readable
        // in /proc and lands in shell history, CI logs and process listings, so
        // the ladder is env -> --passphrase-file -> hidden prompt
        // (TstoreCommand::passphrase()). This test drives the middle rung.
        $passFile = tempnam(sys_get_temp_dir(), 'tfpass-');
        file_put_contents($passFile, "cli-pass\n");

        try {
            $exit = Artisan::call('teleframe:tstore', [
                'action' => 'put',
                'bucket' => 'vault',
                'name' => 'secret.txt',
                'source' => $tmp,
                '--mode' => 'sealed',
                '--passphrase-file' => $passFile,
            ]);
            self::assertSame(TstoreCommand::SUCCESS, $exit);

            Artisan::call('teleframe:tstore', [
                'action' => 'get',
                'bucket' => 'vault',
                'name' => 'secret.txt',
                '--passphrase-file' => $passFile,
            ]);
            self::assertSame('sealed cli payload', rtrim(Artisan::output(), "\n"));
        } finally {
            @unlink($tmp);
            @unlink($passFile);
        }
    }

    public function test_ls_and_verify_and_prune(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tfstore-');
        file_put_contents($tmp, 'verify me via cli');

        try {
            Artisan::call('teleframe:tstore', [
                'action' => 'put',
                'bucket' => 'media-pool',
                'name' => 'v.bin',
                'source' => $tmp,
            ]);

            Artisan::call('teleframe:tstore', ['action' => 'ls', 'bucket' => 'media-pool']);
            self::assertStringContainsString('v.bin', Artisan::output());

            $exit = Artisan::call('teleframe:tstore', ['action' => 'verify', 'bucket' => 'media-pool', 'name' => 'v.bin']);
            self::assertSame(TstoreCommand::SUCCESS, $exit);
            self::assertStringContainsString('verifies OK', Artisan::output());

            Artisan::call('teleframe:tstore', ['action' => 'prune']);
            self::assertStringContainsString('Hot-cache sweep', Artisan::output());
        } finally {
            @unlink($tmp);
        }
    }

    public function test_work_on_sync_driver_reports_nothing_to_drain(): void
    {
        $exit = Artisan::call('teleframe:tstore', ['action' => 'work']);
        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertStringContainsString('Queue driver is sync', Artisan::output());
    }

    public function test_schedules_describes_null_driver(): void
    {
        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedules']);
        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertStringContainsString('Schedule driver: null', Artisan::output());
    }

    public function test_put_without_source_fails(): void
    {
        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'put',
            'bucket' => 'media-pool',
            'name' => 'x.bin',
        ]);
        self::assertSame(TstoreCommand::FAILURE, $exit);
    }
}
