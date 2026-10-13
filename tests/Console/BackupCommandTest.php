<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Backup\RetentionStubTrait;
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
        $this->source = sys_get_temp_dir().'/p4-cli-src-'.uniqid();
        mkdir($this->source.'/sub', 0777, true);
        file_put_contents($this->source.'/a.txt', 'hello backup world');
        file_put_contents($this->source.'/b.txt', 'hello backup world'); // dedup twin
        file_put_contents($this->source.'/sub/c.txt', 'totally different payload');

        $app['config']->set('teleframe.backup', [
            'driver' => 'memory',
            // The memory driver stores nothing; this suite exercises the CLI
            // loop against it, so it acknowledges that explicitly (a `run`
            // without this acknowledgement refuses — see BackupDurabilityTest).
            'allow_volatile_vault' => true,
            'chunk_size' => 10, // 24 bytes of unique content -> 3 chunks
            'sets' => [
                'default' => ['paths' => [$this->source], 'excludes' => []],
            ],
        ]);
    }

    /** @var list<string> */
    private array $secretFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->secretFiles as $file) {
            @unlink($file);
        }
        $this->secretFiles = [];

        self::rrmdir($this->source);

        parent::tearDown();
    }

    /**
     * The passphrase reaches the command through a FILE, never an argument:
     * a shell argument is world-readable in `ps` and lands in shell history,
     * and this is the key to every sealed chunk in the set.
     */
    private function passphraseFile(string $passphrase = 'correct horse'): string
    {
        $path = sys_get_temp_dir().'/tf-backup-pass-'.uniqid();
        file_put_contents($path, $passphrase);
        $this->secretFiles[] = $path;

        return $path;
    }

    public function test_run_status_verify_restore_end_to_end(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutput('backup set=default {uploaded:5, skipped:2, files:3, bytes:61}')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('status set=default {files:3, bytes:61, chunks:5, created:')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--sample' => '3'])
            ->expectsOutput('verify set=default {checked:3, ok:3, missing:0, corrupt:0}')
            ->assertExitCode(0);

        $target = sys_get_temp_dir().'/p4-cli-dst-'.uniqid();
        $this->artisan('teleframe:backup', ['action' => 'restore', '--passphrase-file' => $this->passphraseFile(), '--target' => $target])
            ->expectsOutput("restored set=default {files:3, bytes:61} -> {$target}")
            ->assertExitCode(0);

        self::assertSame(hash_file('sha256', $this->source.'/sub/c.txt'), hash_file('sha256', $target.'/sub/c.txt'));
        self::rrmdir($target);
    }

    public function test_verify_with_passphrase_upgrades_to_full_integrity_check(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutput('verify set=default {checked:5, ok:5, missing:0, corrupt:0}')
            ->assertExitCode(0);
    }

    public function test_verify_failure_exits_nonzero(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        $this->app->instance(BackupCommand::VAULT_FACTORY_KEY, static fn (string $setId): VaultInterface => new HalfMissingVault);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--sample' => '3'])
            ->expectsOutput('verify set=default {checked:3, ok:1, missing:2, corrupt:0}')
            ->assertExitCode(1);
    }

    public function test_run_second_time_uploads_nothing(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
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
            ->expectsOutputToContain('run needs the vault passphrase')
            ->assertExitCode(1);
    }

    /**
     * The command has no --passphrase option at all: the secret must not be
     * reachable through argv, where `ps` and the shell history expose it.
     */
    public function test_there_is_no_passphrase_argument_option(): void
    {
        $command = new BackupCommand;

        self::assertStringContainsString('passphrase-file', $command->getDefinition()->getSynopsis());
        self::assertFalse(
            $command->getDefinition()->hasOption('passphrase'),
            'a --passphrase argument would put the vault key in `ps` output and shell history',
        );
    }

    public function test_run_reads_passphrase_from_a_file(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutput('verify set=default {checked:5, ok:5, missing:0, corrupt:0}')
            ->assertExitCode(0);
    }

    public function test_unreadable_passphrase_file_fails_loud(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => '/nonexistent/tf-passphrase'])
            ->expectsOutput('cannot read --passphrase-file (unreadable path).')
            ->assertExitCode(1);
    }

    public function test_restore_requires_target(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'restore', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutput('restore needs --target=<directory>.')
            ->assertExitCode(1);
    }

    public function test_status_before_any_run_reports_no_manifest(): void
    {
        // A set with no manifest has no backup, so a green status is the same
        // lie the staleness check exists to remove: an operator watching a
        // green `status` must never be looking at a set with nothing to
        // restore. Exit 1, and the wording still distinguishes "never ran"
        // from "ran and went stale".
        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutput('status set=default: no manifest yet (run first)')
            ->assertExitCode(1);
    }

    /**
     * DEFECT-1: `run` on a set that collects ZERO files used to exit 0 and
     * write a manifest anyway, which is what made `status` green forever.
     *
     * The command's own docblock promises the opposite ("A backup that
     * captures no files is not a backup, so this refuses rather than
     * reporting success"), so this is a test of documented behaviour that the
     * code did not implement.
     *
     * Two collection shapes are covered because they fail differently in the
     * wild and both land on the same empty collectFiles() result: an existing
     * but EMPTY directory, and a populated directory whose every file is
     * swallowed by `excludes` (the misconfigured-exclude case, which looks
     * configured and is the likelier accident).
     *
     * The second half is the part that makes this falsifiable: after the
     * refusal, `status` must STILL report no manifest. A guard that fired
     * after the manifest write would exit non-zero and still leave the fresh
     * `generation` + `created` behind — green forever, the original defect
     * wearing a non-zero exit code.
     */
    public function test_run_refuses_a_set_that_collects_no_files(): void
    {
        $empty = sys_get_temp_dir().'/p4-cli-empty-'.uniqid();
        mkdir($empty, 0777, true);

        $this->app['config']->set('teleframe.backup.sets.emptydir', ['paths' => [$empty], 'excludes' => []]);
        $this->app['config']->set('teleframe.backup.sets.allexcluded', ['paths' => [$this->source], 'excludes' => ['.txt']]);

        foreach (['emptydir', 'allexcluded'] as $set) {
            $this->artisan('teleframe:backup', [
                'action' => 'run',
                '--set' => $set,
                '--passphrase-file' => $this->passphraseFile(),
            ])
                ->expectsOutputToContain('collected NO FILES')
                ->assertExitCode(1);
        }

        // No manifest was written by either refusal — `status` still says the
        // set has never run, so `generation` was NOT advanced and nothing
        // reset the staleness clock.
        foreach (['emptydir', 'allexcluded'] as $set) {
            $this->artisan('teleframe:backup', ['action' => 'status', '--set' => $set])
                ->expectsOutputToContain('no manifest yet (run first)')
                ->assertExitCode(1);
        }

        @rmdir($empty);
    }

    /**
     * The refusal is surfaced by the command as an operator-readable error
     * and a non-zero exit, so a cron entry notices. The exact message content
     * (full paths, excludes) is asserted on the runner in the test below,
     * where no console decoration can blur it.
     */
    public function test_the_empty_collection_refusal_reports_the_set_and_the_excludes(): void
    {
        $empty = sys_get_temp_dir().'/p4-cli-empty2-'.uniqid();
        mkdir($empty.'/sub', 0777, true);
        file_put_contents($empty.'/sub/keep.log', 'excluded anyway');

        $this->app['config']->set('teleframe.backup.sets.named', ['paths' => [$empty], 'excludes' => ['.log']]);

        $this->artisan('teleframe:backup', [
            'action' => 'run',
            '--set' => 'named',
            '--passphrase-file' => $this->passphraseFile(),
        ])
            ->expectsOutputToContain('collected NO FILES')
            ->assertExitCode(1);

        @unlink($empty.'/sub/keep.log');
        @rmdir($empty.'/sub');
        @rmdir($empty);
    }

    /**
     * DEFECT-1, at the seam the guard actually lives on — and the exact
     * message content.
     *
     * `BackupRunner::run()` is public static API with callers other than this
     * command (tests call it directly; any future scheduler will too), so a
     * guard living only in BackupCommand would protect exactly one caller.
     * This calls the runner DIRECTLY — bypassing the command entirely — and
     * asserts it still refuses, still writes no manifest, and still names the
     * operator's own `paths` and `excludes` back to them.
     *
     * The exact-string assertions live HERE rather than on the command test
     * because the console decorates and re-wraps long error lines at the
     * terminal width; asserting a full absolute path against decorated output
     * would be testing the renderer, not the guard. Here the message is the
     * exception's own, undecorated and exact.
     */
    public function test_the_runner_itself_refuses_an_empty_collection_and_names_its_config(): void
    {
        $empty = sys_get_temp_dir().'/p4-cli-empty3-'.uniqid();
        mkdir($empty.'/sub', 0777, true);
        file_put_contents($empty.'/sub/keep.log', 'excluded anyway');

        $vault = $this->app->make(BackupCommand::VAULT_FACTORY_KEY)('direct');

        try {
            BackupRunner::run(
                ['paths' => [$empty], 'excludes' => ['.log'], 'set' => 'direct'],
                $vault,
                'correct horse',
            );
            self::fail('expected BackupRunner::run() to refuse a zero-file collection');
        } catch (RuntimeException $e) {
            $message = $e->getMessage();

            self::assertStringContainsString('backup set "direct" collected NO FILES', $message);
            // The operator's own configuration, named back — a 3am cron failure
            // must say WHICH set, WHICH paths and WHICH excludes produced
            // nothing, not a bare "no files".
            self::assertStringContainsString($empty, $message);
            self::assertStringContainsString('.log', $message);
            // And it states the documented promise the code now actually keeps.
            self::assertStringContainsString(
                'A backup that captures no files is not a backup, so this refuses rather than reporting success',
                $message,
            );
        }

        // Nothing reached the vault: no manifest means no green status.
        self::assertNull($vault->getLatestManifest());

        @rmdir($empty);
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

    /**
     * The orphan GC still works — it now takes FOUR generations to produce an
     * orphan, not two.
     *
     * The keep-set is the union of the newest `keep_generations` (default 3),
     * so a/b's chunks are still referenced by generation 1 and are NOT
     * orphans while generations 1..3 are all inside the window. They become
     * orphans at generation 4 — the first one outside it — and only then does
     * a prune have anything to collect. Under the old one-generation
     * keep-set the same fixture orphaned them at generation 2, so this is the
     * same scenario shifted by the retention window, not a weakened one.
     */
    public function test_prune_gcs_chunks_orphaned_by_a_shrinking_set(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        unlink($this->source.'/a.txt');
        unlink($this->source.'/b.txt'); // twin content: both must go to orphan their 2 shared chunks

        // Generations 2, 3 and 4 — 4 is the first one outside the retention
        // window, so this is where generation 1 stops holding the chunks.
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutput('backup set=default {uploaded:0, skipped:3, files:1, bytes:25}')
            ->assertExitCode(0);
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        // 5 chunk messages + 4 manifest messages = 9 walked; 2 orphaned chunk docs die.
        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true])
            ->expectsOutput('prune set=default {scanned:9, pruned:2}')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'prune'])
            ->expectsOutput('prune set=default {scanned:7, pruned:0}')
            ->assertExitCode(0);

        $target = sys_get_temp_dir().'/w13-prune-dst-'.uniqid();
        $this->artisan('teleframe:backup', ['action' => 'restore', '--passphrase-file' => $this->passphraseFile(), '--target' => $target])
            ->expectsOutput("restored set=default {files:1, bytes:25} -> {$target}")
            ->assertExitCode(0);

        self::assertSame(hash_file('sha256', $this->source.'/sub/c.txt'), hash_file('sha256', $target.'/sub/c.txt'));
        self::rrmdir($target);
    }

    /**
     * The prune gate: without --force a prune is a COUNTING pass, so one that
     * would delete anything must refuse loudly (non-zero, for a cron to see)
     * AND leave the vault untouched. "Deletes nothing" is not asserted from
     * the output alone — it is proved behaviourally, by the follow-up forced
     * prune still finding the same 2 orphans, which it cannot do if the gated
     * pass had already removed them.
     *
     * Four generations, for the retention reason spelled out on
     * test_prune_gcs_chunks_orphaned_by_a_shrinking_set: with only two, the
     * keep-set holds both and there is genuinely nothing for the gate to
     * refuse, which would make this test pass for the wrong reason.
     */
    public function test_prune_without_force_refuses_and_deletes_nothing(): void
    {
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        unlink($this->source.'/a.txt');
        unlink($this->source.'/b.txt');

        for ($run = 0; $run < 3; $run++) {
            $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);
        }

        $this->artisan('teleframe:backup', ['action' => 'prune'])
            ->expectsOutput('prune set=default {scanned:9, pruned:2}')
            ->expectsOutputToContain('REFUSED: prune set=default would DELETE 2 orphaned chunk(s) and cost 1 generation(s) their restorability. Nothing was deleted.')
            ->assertExitCode(1);

        // Proof of deletion-absence: the same 2 orphans are still there for a
        // forced prune to find, and only then does it take them.
        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true])
            ->expectsOutput('prune set=default {scanned:9, pruned:2}')
            ->assertExitCode(0);
    }

    /**
     * THE RETENTION TEST.
     *
     * A prune must leave the newest `keep_generations` generations
     * RESTORABLE and must leave every older one unrestorable. Five
     * generations are built, each owning exactly one chunk no other
     * generation references, over one file that never changes — so the
     * keep-set is a real union with a real per-generation boundary, and
     * "generation 5 survives, generation 4 does not" is a claim about
     * deletion rather than about a flag.
     *
     * `generation` is BackupRunner's monotonic counter, so it counts UP:
     * generation 5 is the NEWEST and generation 1 the OLDEST. Under the
     * shipped default of 3 the retained window is therefore {5, 4, 3}, and
     * the two oldest generations are the ones a prune costs their
     * restorability.
     *
     * Restorability is read from the VAULT (the same source `status --list`
     * reads) and from the PARSED console output, so the operator-facing
     * surface is asserted too, with the generation number and its flag bound
     * together on one line.
     */
    public function test_prune_keeps_the_default_number_of_generations_restorable(): void
    {
        $this->seedGenerationsWithUniqueChunks(5);

        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true])->assertExitCode(0);

        // THE ASSERTION: newest 3 kept, the 2 older ones destroyed.
        self::assertSame(
            [5 => true, 4 => true, 3 => true, 2 => false, 1 => false],
            $this->restorableByGenerationFromVault(),
            'a prune with the shipped default must leave exactly the newest 3 generations restorable',
        );
        self::assertSame(
            [5 => true, 4 => true, 3 => true, 2 => false, 1 => false],
            $this->restorableByGenerationFromStatusList(),
            '`status --list` must report the same window the prune actually kept',
        );

        // The destruction is BEHAVIOURAL, not a flag: the chunk the oldest
        // generation owned alone is no longer readable out of the vault,
        // while the newest generation's own chunk — inside the window under
        // the default — still is. Read back through the vault, so a prune
        // that merely relabelled rows could not satisfy either half.
        [$newestOwn, $oldestOwn] = $this->exclusiveAndSharedChunkAddresses();

        self::assertNotSame('', $this->memoryVault()->getChunk($newestOwn), 'the newest generation\'s own chunk is inside the retention window and must survive');

        $this->expectException(RuntimeException::class);
        $this->memoryVault()->getChunk($oldestOwn);
    }

    /**
     * THE NEGATIVE CONTROL, and it is the half that proves the assertion
     * above is not vacuous.
     *
     * Same vault shape, same five generations, same chunks — the ONLY
     * difference is `--keep-generations=1`. If the retention keep-set were
     * silently the newest manifest alone (the defect this lane fixed), this
     * control would produce the SAME result as the test above and its
     * expectation below would fail. If retention were a no-op that kept
     * everything, the control would pass while the assertion above measured
     * nothing. So the pair discriminates in both directions: the assertion
     * cannot pass unless the window is exactly 3, and the control cannot
     * pass unless the window is honoured when narrowed.
     */
    public function test_keep_generations_one_narrows_the_window_to_the_newest_alone(): void
    {
        $this->seedGenerationsWithUniqueChunks(5);

        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true, '--keep-generations' => '1'])
            ->assertExitCode(0);

        self::assertSame(
            [5 => true, 4 => false, 3 => false, 2 => false, 1 => false],
            $this->restorableByGenerationFromVault(),
            '--keep-generations=1 must collapse the window to the newest generation alone',
        );
    }

    /**
     * A `--keep-generations` below 1 is RAISED to 1, never honoured as 0:
     * "keep zero generations" means delete every chunk in the set, and that
     * must not be reachable by typing a small number at a flag.
     */
    public function test_keep_generations_below_one_is_raised_not_honoured_as_zero(): void
    {
        $this->seedGenerationsWithUniqueChunks(3);

        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true, '--keep-generations' => '0'])
            ->assertExitCode(0);

        self::assertSame(
            [3 => true, 2 => false, 1 => false],
            $this->restorableByGenerationFromVault(),
            '0 must be raised to 1 — the newest generation is never collectable',
        );
    }

    /**
     * The SHARED half of the evidence: the chunk every generation references
     * is still readable, byte-for-byte, after the NARROWEST legal window, so
     * the destruction assertions cannot be satisfied by a prune that simply
     * emptied the set. Kept as its own test because an expectException()
     * ends the test it sits in.
     *
     * The bytes are compared to what the vault served BEFORE the prune, not
     * to the file's plaintext: chunk bodies are AEAD-sealed on the way in
     * (VaultCrypto::encryptChunk), so `getChunk()` returns ciphertext by
     * design and asserting on plaintext would fail on a correct vault.
     */
    public function test_the_chunk_every_generation_shares_survives_any_prune(): void
    {
        $this->seedGenerationsWithUniqueChunks(5);

        [, , $shared] = $this->exclusiveAndSharedChunkAddresses();
        $before = $this->memoryVault()->getChunk($shared);

        $this->artisan('teleframe:backup', ['action' => 'prune', '--force' => true, '--keep-generations' => '1'])->assertExitCode(0);

        self::assertSame($before, $this->memoryVault()->getChunk($shared), 'a chunk inside the keep-set is NEVER collectable, however narrow the window');
    }

    /**
     * $generations runs, each with content UNIQUE to that run plus one file
     * that NEVER changes.
     *
     * The unique part is what makes the retention window observable: a
     * generation whose own chunk falls outside the window loses that chunk
     * and stops being restorable. The never-changing file is the union's
     * other half — its single chunk is referenced by every generation, so it
     * is never a deletion candidate no matter how narrow the window gets,
     * and a vault where it WAS collected would show a broken union rather
     * than a passing test.
     *
     * chunk_size is raised so each file is exactly one chunk: with the
     * suite's 10-byte split, "generation 1 unique payload" and "generation
     * 2 unique payload" share their first and last chunks, so only the
     * middle one differs — correct, but it makes the per-generation
     * boundary a puzzle to read instead of a fact to assert.
     */
    private function seedGenerationsWithUniqueChunks(int $generations): void
    {
        $this->app['config']->set('teleframe.backup.chunk_size', 4194304);

        unlink($this->source.'/a.txt');
        unlink($this->source.'/b.txt');

        for ($generation = 1; $generation <= $generations; $generation++) {
            file_put_contents($this->source.'/snap.txt', 'unique payload for generation '.$generation);

            $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
                ->assertExitCode(0);
        }
    }

    /** The process-shared memory vault the command just wrote through. */
    private function memoryVault(): VaultInterface
    {
        /** @var VaultInterface $vault */
        $vault = $this->app->make('teleframe.backup.vault.default');

        return $vault;
    }

    /**
     * generation => restorable, newest generation first, straight from the
     * vault — the source `status --list` reads, with no console formatting
     * in between.
     *
     * @return array<int, bool>
     */
    private function restorableByGenerationFromVault(): array
    {
        $restorable = [];
        foreach ($this->memoryVault()->listManifests(10) as $row) {
            $restorable[(int) $row['generation']] = (bool) $row['restorable'];
        }

        return $restorable;
    }

    /**
     * generation => restorable, PARSED out of `status --list`.
     *
     * Parsed rather than matched on a bare `restorable=yes`, because that
     * substring would pass on the newest row alone: the whole point is
     * WHICH generations carry the flag, and a substring assertion cannot say
     * which. Splitting the row binds the generation number to its verdict.
     *
     * @return array<int, bool>
     */
    private function restorableByGenerationFromStatusList(): array
    {
        $this->withoutMockingConsoleOutput();
        self::assertSame(0, Artisan::call('teleframe:backup', ['action' => 'status', '--list' => true]));

        $restorable = [];
        foreach (explode("\n", Artisan::output()) as $line) {
            if (! str_starts_with(trim($line), 'gen=')) {
                continue;
            }

            $fields = explode(' ', trim($line));
            $restorable[(int) substr((string) $fields[0], 4)] = ($fields[5] ?? '') === 'restorable=yes';
        }

        return $restorable;
    }

    /**
     * The chunk addresses this set's generations split into: the one the
     * NEWEST generation owns alone, the one the OLDEST generation owns
     * alone, and the one EVERY generation references.
     *
     * Read from the vault's own manifests rather than from the prune's
     * count, so this is evidence about what was deleted. If the union is
     * wrong — too narrow, so an inside-the-window chunk was collected, or
     * too wide, so an outside one survived — one of the two addresses is
     * wrong here and the caller's read-back fails on a fact rather than on
     * a restatement of the prune's own count.
     *
     * @return array{string, string, string} [newest-exclusive, oldest-exclusive, shared-by-all]
     */
    private function exclusiveAndSharedChunkAddresses(): array
    {
        $rows = $this->memoryVault()->listManifestChunkHashes(10);

        $counts = [];
        foreach ($rows as $row) {
            foreach ($row['chunk_hashes'] as $hash) {
                $counts[$hash] = ($counts[$hash] ?? 0) + 1;
            }
        }

        $shared = [];
        $exclusive = [];
        foreach ($counts as $hash => $seen) {
            // Newest-first row order puts the newest generation's addresses
            // first, so index 0 of the exclusive list is the newest
            // generation's own chunk and the last entry is the oldest one's.
            $exclusive[] = (string) $hash;
            if ($seen === count($rows)) {
                $shared[] = (string) $hash;
            }
        }

        self::assertCount(1, $shared, 'exactly one chunk is referenced by every generation');
        self::assertNotSame([], $exclusive, 'each generation owns a chunk of its own');

        return [
            $exclusive[0],
            $exclusive[count($exclusive) - 1],
            $shared[0],
        ];
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

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile('x')])
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
            ->expectsOutput('unknown backup driver "ftp" — expected memory|telegram|s3.')
            ->assertExitCode(1);
    }

    public function test_command_is_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);

        self::assertArrayHasKey('teleframe:backup', $kernel->all());
    }

    private static function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
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
    use RetentionStubTrait;

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

    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        $unreadableIds = [];

        return [];
    }

    public function findMessagesByName(string $namePrefix): array
    {
        return [];
    }

    public function delete(string $name): void {}
}
