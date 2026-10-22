<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use Illuminate\Redis\Connections\PhpRedisConnection;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * "The backup stopped running" was UNDETECTABLE (silentest failure in the
 * module): `teleframe:backup status` printed the manifest's `created` and
 * returned SUCCESS unconditionally — nothing compared it to now, and with
 * no Schedule::command('teleframe:backup') registration a cron failing for
 * months produced no line, no exit code, and never even reached the
 * NullLogger default. Prune had the mirror defect: on Redis-unavailable it
 * proceeded WITHOUT the set lock (the exact race the lock prevents) and the
 * only warning was a PSR-3 call the engine's silent default drops, so the
 * console printed a clean success line over a possible data-loss race.
 * And keyless `verify` dropped the `corrupt` counter it computes.
 *
 * Every assertion here is on the CONSOLE surface (output + exit code), not
 * on a logger, because "did the operator see it" is the property under
 * test. Fully offline: a scripted in-memory vault, no network, no stream
 * wrapper, no loop, nothing that can block.
 */
final class BackupStalenessTest extends TestCase
{
    private const HOUR = 3600;

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // NOTE: teleframe.backup.max_age_hours is deliberately NOT set here —
        // it is not declared in src/Laravel/config/teleframe.php, so this
        // suite proves the command's own default (24 h) is applied when the
        // key is absent rather than silently reading a null bound.
        $app['config']->set('teleframe.backup', [
            'driver' => 'memory',
            'allow_volatile_vault' => true,
            'chunk_size' => 4194304,
            'sets' => [
                'default' => ['paths' => [], 'excludes' => []],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        BackupRunner::setLockConnection(null);
        BackupRunner::setLockLogger(null);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function useVault(?array $manifest, array $chunks = [], array $entries = []): void
    {
        $this->app->instance(
            BackupCommand::VAULT_FACTORY_KEY,
            static fn (string $setId): VaultInterface => self::scriptedVault($manifest, $chunks, $entries)
        );
    }

    /**
     * @param  array<string, mixed>|null  $manifest
     * @param  array<string, string>  $chunks
     * @param  list<array{id: string, name: string}>  $entries
     */
    private static function scriptedVault(?array $manifest, array $chunks, array $entries): VaultInterface
    {
        return new class($manifest, $chunks, $entries) implements VaultInterface
        {
            /**
             * @param  array<string, mixed>|null  $manifest
             * @param  array<string, string>  $chunks
             * @param  list<array{id: string, name: string}>  $entries
             */
            public function __construct(
                private ?array $manifest,
                private array $chunks = [],
                private array $entries = [],
            ) {}

            public function putChunk(string $hash, string $bytes): string
            {
                $this->chunks[$hash] = $bytes;

                return $hash;
            }

            public function getChunk(string $hash): string
            {
                return $this->chunks[$hash] ?? throw new RuntimeException('no chunk '.$hash);
            }

            public function putManifest(string $json): string
            {
                /** @var array<string, mixed> $decoded */
                $decoded = json_decode($json, true) ?? [];

                return (string) $this->manifest = $decoded['manifest'] ?? $decoded;
            }

            public function getLatestManifest(): ?array
            {
                return $this->manifest;
            }

            public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
            {
                $unreadableIds = [];
                $manifest = $this->manifest;
                if ($manifest === null) {
                    return [];
                }
                $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
                $bytes = 0;
                foreach ($files as $file) {
                    $bytes += is_array($file) && is_int($file['size'] ?? null) ? $file['size'] : 0;
                }

                return [[
                    'generation' => is_int($manifest['generation'] ?? null) ? $manifest['generation'] : 0,
                    'created' => is_string($manifest['created'] ?? null) ? $manifest['created'] : 'unknown',
                    'files' => count($files),
                    'chunks' => count(is_array($manifest['chunk_hashes'] ?? null) ? $manifest['chunk_hashes'] : []),
                    'bytes' => $bytes,
                    'restorable' => true,
                    'missing_chunks' => 0,
                ]];
            }

            /**
             * Retention: the one row this scripted vault can honestly report
             * (it holds ONE manifest). Inline rather than the shared
             * RetentionStubTrait because an anonymous class cannot `use` a
             * named trait without colliding with this file's own imports.
             *
             * @return list<array{generation: int, created: string, chunk_hashes: list<string>}>
             */
            public function listManifestChunkHashes(int $generations = 2): array
            {
                if ($this->manifest === null) {
                    return [];
                }

                $hashes = is_array($this->manifest['chunk_hashes'] ?? null) ? $this->manifest['chunk_hashes'] : [];

                return [[
                    'generation' => is_int($this->manifest['generation'] ?? null) ? $this->manifest['generation'] : 0,
                    'created' => is_string($this->manifest['created'] ?? null) ? $this->manifest['created'] : 'unknown',
                    'chunk_hashes' => array_values(array_filter($hashes, 'is_string')),
                ]];
            }

            public function findMessagesByName(string $namePrefix): array
            {
                $out = [];
                foreach ($this->entries as $entry) {
                    if (str_starts_with($entry['name'], $namePrefix)) {
                        $out[] = $entry;
                    }
                }

                return $out;
            }

            public function delete(string $name): void
            {
                $this->entries = array_values(array_filter(
                    $this->entries,
                    static fn (array $e): bool => $e['name'] !== $name
                ));
            }
        };
    }

    /**
     * A manifest stamped $ageHours in the past, in the shape BackupRunner
     * writes (gmdate('c')).
     *
     * @return array<string, mixed>
     */
    private static function manifestAged(int $ageHours, array $extra = []): array
    {
        return $extra + [
            'set' => 'default',
            'generation' => 1,
            'created' => gmdate('c', time() - ($ageHours * self::HOUR)),
            'files' => [['path' => 'a.txt', 'size' => 5]],
            'chunk_hashes' => [],
        ];
    }

    /** (a) An old manifest must FAIL and print the age — the whole point. */
    public function test_status_fails_and_names_the_age_when_the_manifest_is_stale(): void
    {
        $this->useVault(self::manifestAged(40));

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('age_hours:40, max_age_hours:24')
            ->expectsOutputToContain('STALE: the newest manifest for set=default is 40 h old, over the 24 h bound')
            ->assertExitCode(1);
    }

    /** (a-bis) The bound is CONFIGURABLE, not hardcoded: 72 h accepts a 40 h manifest. */
    public function test_status_honours_a_configured_bound(): void
    {
        $this->app['config']->set('teleframe.backup.max_age_hours', 72);
        $this->useVault(self::manifestAged(40));

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('max_age_hours:72')
            ->assertExitCode(0);
    }

    /** (b) A manifest inside the bound stays SUCCESS — no false alarm. */
    public function test_status_succeeds_on_a_fresh_manifest(): void
    {
        $this->useVault(self::manifestAged(2));

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('age_hours:2, max_age_hours:24')
            ->assertExitCode(0);
    }

    /**
     * (c) NO MANIFEST AT ALL fails loudly. Decided: for a first release a
     * set with no manifest is a set with NO BACKUP, and reporting success
     * there is the same lie the whole defect is about — an operator
     * watching a green `status` must never be looking at a set that has
     * nothing to restore. The message distinguishes it from staleness so
     * the operator knows to run once, not to chase a failing cron.
     */
    public function test_status_fails_loudly_when_no_manifest_exists_at_all(): void
    {
        $this->useVault(null);

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('status set=default: no manifest yet (run first)')
            ->expectsOutputToContain('STALE: set=default has NO manifest at all')
            ->assertExitCode(1);
    }

    /**
     * A manifest whose `created` is unreadable cannot be proven fresh, so
     * it is treated as stale (fails safe) rather than waved through.
     */
    public function test_status_fails_when_the_created_timestamp_is_unreadable(): void
    {
        $this->useVault(self::manifestAged(0, ['created' => 'not-a-timestamp']));

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('age_hours:unknown')
            ->expectsOutputToContain('no readable `created` timestamp')
            ->assertExitCode(1);
    }

    /**
     * (d) Redis unavailable -> prune proceeds WITHOUT the lock. That must be
     * on the CONSOLE, not in a PSR-3 warning the engine's NullLogger default
     * drops — this is the silent data-loss race (chunks of an in-flight
     * backup deleted, next manifest still referencing them).
     */
    public function test_prune_warns_on_the_console_when_the_lock_is_unavailable(): void
    {
        BackupRunner::setLockConnection(null); // no Redis-backed store => unavailable

        $orphan = str_repeat('ab', 32); // 64 lowercase hex => chunk-shaped
        $this->useVault(
            self::manifestAged(1, ['chunk_hashes' => []]),
            [],
            [['id' => '1', 'name' => $orphan]]
        );

        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true])
            ->expectsOutputToContain('prune set=default ran WITHOUT the lock (Redis unavailable)')
            ->expectsOutputToContain('prune set=default {scanned:1, pruned:1}')
            ->assertExitCode(0);
    }

    /**
     * A HELD lock is a different verdict (an in-flight backup; prune skips):
     * it must NOT print the no-lock warning, or the message means nothing —
     * that half of the intent is unchanged and is asserted below.
     *
     * The exit code is what moved. The lock verdict short-circuits BEFORE the
     * inventory walk (Pruner::pruneReportingLock returns scanned:0 without
     * touching a single entry), so `pruned:0` on that line is not a count, it
     * is the absence of a prune — and returning SUCCESS over it let a cron
     * read a retention pass that never ran as a clean one. It is now
     * FAILURE, carrying the same {scanned:0, pruned:0} line plus a SKIPPED
     * line that names the held lock. This is NOT the Redis-unavailable path:
     * there prune really walks, `pruned:0` is a true answer, and SUCCESS is
     * correct — see test_prune_warns_on_the_console_when_the_lock_is_unavailable.
     */
    public function test_prune_fails_loudly_when_the_lock_is_held_by_a_backup(): void
    {
        BackupRunner::setLockConnection(new HeldLockConnection);
        BackupRunner::acquireSetLock('default'); // take the lock as a runner would

        $this->useVault(self::manifestAged(1));

        $this->artisan('teleframe:backup', ['action' => 'prune'])
            ->expectsOutputToContain('SKIPPED: prune set=default was NOT run — a backup holds the set lock, so nothing was scanned and nothing was deleted.')
            // the misleading no-lock warning stays absent: the lock IS held
            ->doesntExpectOutputToContain('ran WITHOUT the lock')
            ->expectsOutput('prune set=default {scanned:0, pruned:0}')
            ->assertExitCode(1);
    }

    /**
     * (e) The keyless verify path DID count `corrupt` and threw the number
     * away: the exit code said failure while the printed line said
     * `ok:3 missing:0`, leaving the operator no reason at all.
     */
    public function test_keyless_verify_prints_the_corrupt_counter(): void
    {
        $plain = str_repeat('cd', 32);
        $wire = str_repeat('ef', 32);

        $this->useVault(
            self::manifestAged(1, [
                'chunk_hashes' => [$plain],
                // wire_chunks is the CIPHERTEXT → plaintext address book
                // (BackupRunner:37): the manifest names a wire hash the
                // stored bytes do not match => corrupt, not missing.
                'wire_chunks' => [$wire => $plain],
            ]),
            [$plain => 'bytes that are not the named ciphertext']
        );

        $this->artisan('teleframe:backup', ['action' => 'verify', '--sample' => '1'])
            ->expectsOutputToContain('verify set=default {checked:1, ok:0, missing:0, corrupt:1}')
            ->assertExitCode(1);
    }
}

/**
 * A lock connection that is ALWAYS held, standing in for an in-flight
 * backup: `SET … NX` fails, so acquireSetLock() reports acquired=false /
 * unavailable=false — the "skip the prune" verdict, not the "no Redis"
 * one. Bounded and offline; no socket is opened.
 */
final class HeldLockConnection extends PhpRedisConnection
{
    public function __construct()
    {
        parent::__construct(new \stdClass);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function command($method, array $parameters = [])
    {
        return false; // every SET NX fails: somebody else holds the key
    }
}
