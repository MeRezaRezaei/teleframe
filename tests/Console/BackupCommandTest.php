<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * teleframe:backup (plan Phase 4, Task 4): the full CLI loop —
 * run → status → verify → restore — against a tmpdir backup set with the
 * offline memory driver. The vault factory seam and the telegram-driver
 * scope wiring (P3 SCOPE_RESOLVER_KEY reuse) are exercised separately.
 */
final class BackupCommandTest extends TestCase
{
    private string $source;

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->source = sys_get_temp_dir() . '/p4-cli-src-' . uniqid();
        mkdir($this->source . '/sub', 0777, true);
        file_put_contents($this->source . '/a.txt', 'hello backup world');
        file_put_contents($this->source . '/b.txt', 'hello backup world'); // dedup twin
        file_put_contents($this->source . '/sub/c.txt', 'totally different payload');

        $app['config']->set('teleframe.backup', [
            'driver' => 'memory',
            'chunk_size' => 10, // 24 bytes of unique content -> 3 chunks
            'sets' => [
                'default' => ['paths' => [$this->source], 'excludes' => []],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        self::rrmdir($this->source);

        parent::tearDown();
    }

    public function test_run_status_verify_restore_end_to_end(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])
            ->expectsOutput('backup set=default {uploaded:5, skipped:2, files:3, bytes:61}')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('status set=default {files:3, bytes:61, chunks:5, created:')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--sample' => '3'])
            ->expectsOutput('verify set=default {checked:3, ok:3, missing:0}')
            ->assertExitCode(0);

        $target = sys_get_temp_dir() . '/p4-cli-dst-' . uniqid();
        $this->artisan('teleframe:backup', ['action' => 'restore', '--passphrase' => 'correct horse', '--target' => $target])
            ->expectsOutput("restored set=default {files:3, bytes:61} -> {$target}")
            ->assertExitCode(0);

        self::assertSame(hash_file('sha256', $this->source . '/sub/c.txt'), hash_file('sha256', $target . '/sub/c.txt'));
        self::rrmdir($target);
    }

    public function test_verify_with_passphrase_upgrades_to_full_integrity_check(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--passphrase' => 'correct horse'])
            ->expectsOutput('verify set=default {checked:5, ok:5, missing:0, corrupt:0}')
            ->assertExitCode(0);
    }

    public function test_verify_failure_exits_nonzero(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])->assertExitCode(0);

        $this->app->instance(BackupCommand::VAULT_FACTORY_KEY, static fn (string $setId): VaultInterface => new HalfMissingVault());

        $this->artisan('teleframe:backup', ['action' => 'verify', '--sample' => '3'])
            ->expectsOutput('verify set=default {checked:3, ok:1, missing:2}')
            ->assertExitCode(1);
    }

    public function test_run_second_time_uploads_nothing(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])
            ->expectsOutput('backup set=default {uploaded:0, skipped:7, files:3, bytes:61}')
            ->assertExitCode(0);
    }

    public function test_run_reads_passphrase_from_env(): void
    {
        putenv('TELEFRAME_BACKUP_PASSPHRASE=env secret');

        try {
            $this->artisan('teleframe:backup', ['action' => 'run'])->assertExitCode(0);
        } finally {
            putenv('TELEFRAME_BACKUP_PASSPHRASE');
        }
    }

    public function test_run_requires_passphrase(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run'])
            ->expectsOutput('run needs --passphrase= or TELEFRAME_BACKUP_PASSPHRASE.')
            ->assertExitCode(1);
    }

    public function test_restore_requires_target(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'restore', '--passphrase' => 'correct horse'])
            ->expectsOutput('restore needs --target=<directory>.')
            ->assertExitCode(1);
    }

    public function test_status_before_any_run_reports_no_manifest(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutput('status set=default: no manifest yet (run first)')
            ->assertExitCode(0);
    }

    public function test_unknown_action_fails_loud(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'explode'])
            ->expectsOutput('unknown action "explode" — expected run|restore|verify|status|prune.')
            ->assertExitCode(1);
    }

    public function test_unknown_set_fails_loud(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'status', '--set' => 'nope'])
            ->expectsOutput('unknown backup set "nope" — configure teleframe.backup.sets.nope.')
            ->assertExitCode(1);
    }

    public function test_prune_gcs_chunks_orphaned_by_a_shrinking_set(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])->assertExitCode(0);

        unlink($this->source . '/a.txt');
        unlink($this->source . '/b.txt'); // twin content: both must go to orphan their 2 shared chunks

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'correct horse'])
            ->expectsOutput('backup set=default {uploaded:0, skipped:3, files:1, bytes:25}')
            ->assertExitCode(0);

        // 5 chunk messages + 2 manifest messages = 7 walked; 2 orphaned chunk docs die.
        $this->artisan('teleframe:backup', ['action' => 'prune'])
            ->expectsOutput('prune set=default {scanned:7, pruned:2}')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'prune'])
            ->expectsOutput('prune set=default {scanned:5, pruned:0}')
            ->assertExitCode(0);

        $target = sys_get_temp_dir() . '/w13-prune-dst-' . uniqid();
        $this->artisan('teleframe:backup', ['action' => 'restore', '--passphrase' => 'correct horse', '--target' => $target])
            ->expectsOutput("restored set=default {files:1, bytes:25} -> {$target}")
            ->assertExitCode(0);

        self::assertSame(hash_file('sha256', $this->source . '/sub/c.txt'), hash_file('sha256', $target . '/sub/c.txt'));
        self::rrmdir($target);
    }

    public function test_prune_without_manifest_fails_loud(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'prune'])
            ->expectsOutput('prune needs a manifest first (run).')
            ->assertExitCode(1);
    }

    public function test_telegram_driver_resolves_scope_through_the_backfill_seam(): void
    {
        config()->set('teleframe.backup.driver', 'telegram');
        config()->set('teleframe.backup.account', 7);

        $this->app->bind(BackfillCommand::SCOPE_RESOLVER_KEY, static function () {
            throw new RuntimeException('scope resolver reached');
        });

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase' => 'x'])
            ->expectsOutputToContain('scope resolver reached')
            ->assertExitCode(1);
    }

    public function test_telegram_driver_requires_configured_account(): void
    {
        config()->set('teleframe.backup.driver', 'telegram');
        config()->set('teleframe.backup.account', null);

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutput('backup driver "telegram" needs teleframe.backup.account (a daemon.accounts account_id).')
            ->assertExitCode(1);
    }

    public function test_unknown_driver_fails_loud(): void
    {
        config()->set('teleframe.backup.driver', 'ftp');

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutput('unknown backup driver "ftp" — expected memory|telegram.')
            ->assertExitCode(1);
    }

    public function test_command_is_registered(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        self::assertArrayHasKey('teleframe:backup', $kernel->all());
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? self::rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

/**
 * Vault that only serves half its manifest — drives the verify FAILURE path.
 */
final class HalfMissingVault implements VaultInterface
{
    public function putChunk(string $hash, string $bytes): string
    {
        return '1';
    }

    public function getChunk(string $hash): string
    {
        if ($hash === 'h1') {
            return 'served';
        }

        throw new RuntimeException("chunk {$hash} gone");
    }

    public function putManifest(string $json): string
    {
        return '2';
    }

    public function getLatestManifest(): ?array
    {
        return [
            'version' => 1,
            'salt' => str_repeat('ab', 16),
            'chunk_hashes' => ['h1', 'h2', 'h3'],
        ];
    }

    public function findMessagesByName(string $namePrefix): array
    {
        return [];
    }

    public function delete(string $name): void
    {
    }
}
