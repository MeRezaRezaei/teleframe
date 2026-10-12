<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\FakeVaultApi;
use Orchestra\Testbench\TestCase;

/**
 * THE BACKUP HISTORY (VaultInterface::listManifests + `status --list`).
 *
 * Backup staleness was checkable but the set's DEPTH was not: nothing
 * reported how many generations existed, how old the oldest was, or whether
 * an old generation could still be restored. This suite pins the three
 * properties that make the listing worth trusting:
 *
 * 1. ORDER IS BY THE SEALED GENERATION, NOT BY MESSAGE ID. Every fixture
 *    here posts generations OUT OF ORDER, so a listing that ranked by
 *    message id (or that simply reversed the message stack) produces a
 *    visibly different answer.
 * 2. AN UNREADABLE MANIFEST IS SKIPPED AND REPORTED, never silently
 *    dropped — its message id comes back through $unreadableIds. The one
 *    exception is the NEWEST manifest post, which fails loud, because an
 *    unreadable current state must not be papered over by promoting an
 *    older generation to "latest".
 * 3. RESTORABILITY IS A HARD ASSERTION, not a guess: a generation whose
 *    chunks the vault no longer holds (a prune deleted them) is flagged
 *    `restorable=false` with the count of missing chunks.
 *
 * Everything here is offline: InMemoryVault and the in-memory
 * FakeVaultApi. No network, no credentials, no stream wrappers, no waits.
 */
final class ManifestHistoryTest extends TestCase
{
    private const PASSPHRASE = 'history suite passphrase';

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('teleframe.backup', [
            'driver' => 'memory',
            'allow_volatile_vault' => true,
            'sets' => ['default' => ['paths' => [], 'excludes' => []]],
        ]);
    }

    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 2).'/tests/Schema/FixesAutoload.php';
    }

    /**
     * A manifest body shaped exactly as BackupRunner writes it.
     *
     * @param  list<string>  $chunks
     * @param  array<string, string>  $wire  ciphertext sha → plaintext sha
     * @param  int  $bytes  the generation's TOTAL bytes, split across its files
     * @return array<string, mixed>
     */
    private static function manifest(int $generation, string $created, array $chunks = [], array $wire = [], int $files = 1, int $bytes = 100): array
    {
        $perFile = $files > 0 ? intdiv($bytes, $files) : 0;

        return [
            'version' => 2,
            'set' => 'default',
            'generation' => $generation,
            'salt' => bin2hex(str_repeat('ab', 16)),
            'created' => $created,
            'files' => array_fill(0, max(0, $files), ['path' => 'a.txt', 'size' => $perFile, 'chunks' => $chunks]),
            'chunk_hashes' => $chunks,
            'wire_chunks' => $wire,
        ];
    }

    /**
     * Three generations POSTED OUT OF GENERATION ORDER: 7 first (so it has
     * the HIGHEST message id), then 2, then 5. Message order is therefore
     * 7, 2, 5 while generation order is 7, 5, 2 — the two disagree, which
     * is the whole point of ranking by generation.
     */
    private static function seededVault(): InMemoryVault
    {
        $vault = new InMemoryVault;

        // The chunk documents the generations reference, so every row is
        // genuinely restorable (the broken-generation case has its own test).
        foreach (['c1', 'c2', 'c3'] as $chunk) {
            $vault->putChunk($chunk, 'cipher-'.$chunk);
        }

        $vault->putManifest((string) json_encode(self::manifest(7, self::hoursAgo(1), ['c1', 'c2', 'c3'], [], 3, 300)));
        $vault->putManifest((string) json_encode(self::manifest(2, self::hoursAgo(49), ['c1'], [], 1, 100)));
        $vault->putManifest((string) json_encode(self::manifest(5, self::hoursAgo(25), ['c1', 'c2'], [], 2, 200)));

        return $vault;
    }

    /**
     * A timestamp relative to NOW, not a pinned constant.
     *
     * These fixtures used to hardcode absolute dates, which made them a time
     * bomb: the newest generation aged past `teleframe.backup.max_age_hours`
     * two days later, `status` correctly reported the set as STALE and exited
     * 1, and two CLI tests went red in CI while passing locally. A fixture
     * that decays is a fixture that will fail again. The newest generation is
     * 1h old — inside the 24h bound, so the set reads fresh — while the older
     * two still span a realistic multi-day gap.
     */
    private static function hoursAgo(int $hours): string
    {
        return gmdate('c', time() - ($hours * 3600));
    }

    // ── ordering, counts, limit ────────────────────────────────────────

    public function test_history_lists_newest_generation_first_with_each_generations_own_counts(): void
    {
        $rows = self::seededVault()->listManifests();

        self::assertSame([7, 5, 2], array_column($rows, 'generation'), 'newest GENERATION first, not newest message');
        self::assertSame(
            [self::hoursAgo(1), self::hoursAgo(25), self::hoursAgo(49)],
            array_column($rows, 'created'),
        );
        self::assertSame([3, 2, 1], array_column($rows, 'files'));
        self::assertSame([3, 2, 1], array_column($rows, 'chunks'), 'each row counts its OWN generation');
        self::assertSame([300, 200, 100], array_column($rows, 'bytes'));
    }

    public function test_ordering_is_by_generation_and_not_by_insertion_order(): void
    {
        $rows = self::seededVault()->listManifests();

        $byMessageOrder = [7, 2, 5]; // the order the posts were made in
        self::assertNotSame(
            $byMessageOrder,
            array_column($rows, 'generation'),
            'a listing in message order would be 7,2,5 — the fixture is built so the two differ',
        );
        self::assertSame([7, 5, 2], array_column($rows, 'generation'));
    }

    public function test_limit_is_respected_and_returns_the_newes_t_generations(): void
    {
        $vault = self::seededVault();

        $rows = $vault->listManifests(2);

        self::assertCount(2, $rows);
        self::assertSame([7, 5], array_column($rows, 'generation'), 'the cap keeps the NEWEST, it does not sample');

        self::assertCount(3, $vault->listManifests(3), 'a limit above the generation count returns them all');
        self::assertCount(1, $vault->listManifests(1), 'limit=1 returns exactly the newest');
        self::assertCount(1, $vault->listManifests(0), 'a nonsensical limit is raised to 1, never to 0 rows');
    }

    public function test_get_latest_manifest_agrees_with_the_first_history_row(): void
    {
        $vault = self::seededVault();

        $rows = $vault->listManifests();
        $latest = $vault->getLatestManifest();

        self::assertNotNull($latest);
        self::assertSame($rows[0]['generation'], $latest['generation'], 'listManifests()[0] IS the latest manifest');
        self::assertSame(7, $latest['generation']);
        self::assertSame(7, $rows[0]['generation'], 'a generation-order regression flips this: 2 (newest post) would win');
    }

    public function test_an_empty_vault_lists_nothing_and_has_no_latest_manifest(): void
    {
        $vault = new InMemoryVault;

        self::assertSame([], $vault->listManifests());
        self::assertSame([], $vault->listManifests(5, $unreadable), 'the by-ref report is filled even when empty');
        self::assertNull($vault->getLatestManifest());
    }

    // ── unreadable manifests: skipped AND reported ─────────────────────

    public function test_an_undecryptable_manifest_is_skipped_and_reported_not_silently_dropped(): void
    {
        $vault = new InMemoryVault;
        $vault->putManifest('this is not JSON at all'); // an unreadable post
        $vault->putManifest((string) json_encode(self::manifest(4, '2026-09-26T00:00:00+00:00')));
        $vault->putManifest((string) json_encode(self::manifest(9, '2026-09-27T00:00:00+00:00')));

        $unreadable = [];
        $rows = $vault->listManifests(10, $unreadable);

        self::assertSame([9, 4], array_column($rows, 'generation'), 'the unreadable post is skipped, not presented as history');
        self::assertCount(1, $unreadable, 'and it is REPORTED: one unreadable manifest post');
        self::assertSame([1], $unreadable, 'reported by its message id');
        self::assertSame(9, $vault->getLatestManifest()['generation'], 'an unreadable OLDER post does not derail the latest lookup');
    }

    public function test_an_unreadable_newes_t_manifest_fails_loud_instead_of_promoting_an_older_generation(): void
    {
        $vault = new InMemoryVault;
        $vault->putManifest((string) json_encode(self::manifest(4, '2026-09-26T00:00:00+00:00')));
        $vault->putManifest('not JSON — the current state is unreadable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not valid JSON');

        $vault->listManifests();
    }

    public function test_a_sealed_manifest_undecryptable_under_the_vault_key_is_skipped_and_reported(): void
    {
        $api = new FakeVaultApi;
        $api->addChannel(TelegramVault::channelTitle('set1'));
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE);

        $vault->putManifest((string) json_encode(self::manifest(3, '2026-09-26T00:00:00+00:00')));
        // A post sealed under ANOTHER key: real AEAD, undecryptable here.
        $foreign = new TelegramVault('set1', $api->map(), 'a different passphrase');
        $foreign->putManifest((string) json_encode(self::manifest(8, '2026-09-27T00:00:00+00:00')));
        $vault->putManifest((string) json_encode(self::manifest(5, '2026-09-26T12:00:00+00:00')));

        $unreadable = [];
        $rows = $vault->listManifests(10, $unreadable);

        self::assertSame([5, 3], array_column($rows, 'generation'), 'the foreign-key post is skipped, never listed as a generation');
        self::assertCount(1, $unreadable, 'and REPORTED by message id');
        self::assertSame(5, $vault->getLatestManifest()['generation'], 'the latest lookup is unaffected by the skipped post');
    }

    // ── restorability: a generation a prune broke ──────────────────────

    public function test_a_generation_whose_chunks_are_gone_is_flagged_unrestorable(): void
    {
        $vault = new InMemoryVault;
        $wireA = str_repeat('a', 64);
        $wireB = str_repeat('b', 64);

        // Generation 2 owns two documents; both are present.
        $vault->putChunk($wireA, 'cipher-a');
        $vault->putChunk($wireB, 'cipher-b');
        $vault->putManifest((string) json_encode(self::manifest(
            2,
            '2026-09-26T00:00:00+00:00',
            ['plain-a', 'plain-b'],
            [$wireA => 'plain-a', $wireB => 'plain-b'],
        )));

        // Generation 3 reuses plain-a and adds plain-c, whose document is
        // never stored — the shape a prune leaves behind when it deleted a
        // chunk the older manifest still references.
        $vault->putManifest((string) json_encode(self::manifest(
            3,
            '2026-09-27T00:00:00+00:00',
            ['plain-a', 'plain-c'],
            [$wireA => 'plain-a', str_repeat('c', 64) => 'plain-c'],
        )));

        $rows = $vault->listManifests();

        self::assertSame([3, 2], array_column($rows, 'generation'));
        self::assertFalse($rows[0]['restorable'], 'generation 3 references a document the vault does not hold');
        self::assertSame(1, $rows[0]['missing_chunks'], 'exactly the one missing document');
        self::assertTrue($rows[1]['restorable'], 'generation 2 references only stored documents');
        self::assertSame(0, $rows[1]['missing_chunks']);

        // …and once the shared document is pruned away, generation 2 breaks
        // too, which is the whole point of the flag.
        $vault->delete($wireA);

        $rows = $vault->listManifests();
        self::assertFalse($rows[1]['restorable'], 'a generation referencing a pruned chunk cannot be restored');
        self::assertSame(1, $rows[1]['missing_chunks']);
    }

    // ── the CLI surface: `status --list` ───────────────────────────────

    public function test_status_list_prints_a_header_and_one_line_per_generation_plus_a_total(): void
    {
        $this->app->instance(BackupCommand::VAULT_FACTORY_KEY, fn (string $setId): VaultInterface => self::seededVault());

        $this->artisan('teleframe:backup', ['action' => 'status', '--list' => true])
            ->expectsOutput('history set=default {generations:3}')
            ->expectsOutput('  gen=7 created='.self::hoursAgo(1).' files=3 chunks=3 bytes=300 restorable=yes missing_chunks=0')
            ->expectsOutput('  gen=5 created='.self::hoursAgo(25).' files=2 chunks=2 bytes=200 restorable=yes missing_chunks=0')
            ->expectsOutput('  gen=2 created='.self::hoursAgo(49).' files=1 chunks=1 bytes=100 restorable=yes missing_chunks=0')
            ->expectsOutput('history-total set=default {generations:3, files:6, chunks:6, bytes:600, oldest_generation:2, oldest_created:'.self::hoursAgo(49).'}')
            ->expectsOutputToContain('status set=default {files:')
            ->assertExitCode(0);
    }

    public function test_status_list_warns_about_a_generation_a_prune_broke(): void
    {
        $vault = new InMemoryVault;
        $vault->putChunk(str_repeat('a', 64), 'cipher-a');
        $vault->putManifest((string) json_encode(self::manifest(
            3,
            gmdate('c'),
            ['plain-a', 'plain-c'],
            [str_repeat('a', 64) => 'plain-a', str_repeat('c', 64) => 'plain-c'],
        )));

        $this->app->instance(BackupCommand::VAULT_FACTORY_KEY, fn (string $setId): VaultInterface => $vault);

        $this->artisan('teleframe:backup', ['action' => 'status', '--list' => true])
            ->expectsOutput('  gen=3 created='.gmdate('c').' files=1 chunks=2 bytes=100 restorable=NO missing_chunks=1')
            ->expectsOutputToContain('CANNOT be restored')
            ->assertExitCode(0);
    }

    public function test_status_list_reports_manifest_posts_it_could_not_read(): void
    {
        $vault = new InMemoryVault;
        $vault->putManifest('not JSON at all');
        $vault->putManifest((string) json_encode(self::manifest(6, gmdate('c'))));

        $this->app->instance(BackupCommand::VAULT_FACTORY_KEY, fn (string $setId): VaultInterface => $vault);

        $this->artisan('teleframe:backup', ['action' => 'status', '--list' => true])
            ->expectsOutput('history set=default {generations:1}')
            ->expectsOutputToContain('could not be read (message ids 1)')
            ->assertExitCode(0);
    }

    public function test_status_without_list_prints_no_history(): void
    {
        $this->app->instance(BackupCommand::VAULT_FACTORY_KEY, fn (string $setId): VaultInterface => self::seededVault());

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->doesntExpectOutputToContain('history set=default')
            ->assertExitCode(0);
    }
}
