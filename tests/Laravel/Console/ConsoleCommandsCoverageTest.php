<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Console\AppCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\CluesCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\DoctorCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestGateCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\LoginCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\PassportCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\PollCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\RoutingModeCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaUpdateCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SuperviseCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultUseDefaultCommand;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * W-19: PollCommand, LoginCommand and SchemaUpdateCommand previously had
 * ZERO tests. Now that the fix wave is merged onto main (real vendor, not a
 * pre-fix symlink) and CI is clean-checkout-safe, the honest testable seams
 * are exercised:
 *
 *  - PollCommand:    the update-summary renderer (pure format logic; the
 *                    poll loop itself is a live, credential-gated run).
 *  - LoginCommand:   the vault-app resolution gate — the ONLY pre-prompt
 *                    seam in a TTY-bound wizard. Unknown label fails the
 *                    command; absent label must NOT touch the vault DB.
 *  - SchemaUpdateCommand: the --dry-run path regenerates into a scratch dir
 *                    and leaves the committed repo byte-for-byte untouched —
 *                    the exact hazard the fix wave cleared (subprocess regen
 *                    used to resolve through the vendor symlink to the
 *                    pre-fix main tree).
 *
 * Interactive prompts / live RPC remain untested by design: they need a TTY
 * and real credentials, which the repo gates forbid in CI.
 */
final class ConsoleCommandsCoverageTest extends TestCase
{
    public function test_login_unknown_vault_app_fails_before_prompt(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new LoginCommand);

        // Migrate only the vault apps table (sqlite :memory:) so the gate's
        // query runs against a real table and reports "unknown vault app"
        // rather than the missing-table hint.
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 3).'/src/Laravel/Migrations/2026_09_09_000200_create_telegram_apps_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);

        $exit = Artisan::call('teleframe:login', ['--app' => 'no-such-app']);

        self::assertSame(LoginCommand::FAILURE, $exit);
        self::assertStringContainsString(
            "unknown vault app 'no-such-app'",
            Artisan::output(),
        );
    }

    public function test_login_vault_app_label_without_db_fails_cleanly(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new LoginCommand);

        // No migrations ran in this test case: the vault table does not
        // exist. resolveVaultApp must catch the query failure and report the
        // "run migrations first" hint instead of crashing.
        $exit = Artisan::call('teleframe:login', ['--app' => 'app-that-needs-db']);

        self::assertSame(LoginCommand::FAILURE, $exit);
        self::assertStringContainsString('Vault unreachable', Artisan::output());
    }

    private function pollWith(array $update): string
    {
        $command = new class extends PollCommand
        {
            public function handle(): int
            {
                return self::SUCCESS;
            }
        };
        $output = new BufferedOutput;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput([]), $output);
        // run() bound $this->output; now drive the renderer directly.
        (function () use ($update): void {
            $this->displayUpdateSummary($update);
        })->call($command);

        return $output->fetch();
    }

    public function test_poll_summary_renders_message_update(): void
    {
        $line = $this->pollWith([
            'update_id' => 7,
            'message' => [
                'from' => ['username' => 'alice', 'first_name' => 'Alice'],
                'text' => 'hello',
            ],
        ]);

        self::assertStringContainsString('[#7]', $line);
        self::assertStringContainsString('alice', $line);
        self::assertStringContainsString('hello', $line);
        self::assertStringNotContainsString('Callback', $line);
    }

    public function test_poll_summary_renders_callback_query_update(): void
    {
        $line = $this->pollWith([
            'update_id' => 8,
            'callback_query' => [
                'from' => ['username' => 'bob'],
                'data' => 'btn:open',
            ],
        ]);

        self::assertStringContainsString('[#8]', $line);
        self::assertStringContainsString('bob', $line);
        self::assertStringContainsString('btn:open', $line);
    }

    public function test_poll_summary_renders_unknown_update_type(): void
    {
        $line = $this->pollWith([
            'update_id' => 9,
            'my_chat_member' => ['whatever' => 1],
        ]);

        self::assertStringContainsString('[#9]', $line);
        self::assertStringContainsString('my_chat_member', $line);
    }

    public function test_schema_update_dry_run_leaves_repo_untouched(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new SchemaUpdateCommand);

        $schemaDir = SchemaAuditCommand::schemaDir();
        $before = [
            file_get_contents("{$schemaDir}/methods-mtproto.json") ?: '',
            file_get_contents("{$schemaDir}/methods-botapi.json") ?: '',
            file_exists("{$schemaDir}/schema-manifest.json") ? file_get_contents("{$schemaDir}/schema-manifest.json") : '',
        ];

        $exit = Artisan::call('teleframe:schema-update', ['--no-fetch' => true, '--dry-run' => true]);

        self::assertSame(SchemaUpdateCommand::SUCCESS, $exit);
        self::assertStringContainsString('dry-run, repo untouched', Artisan::output());

        // The committed artifacts are byte-identical after a dry run: the
        // regenerated output went to scratch, never into src/Schema/schema.
        self::assertSame(
            $before,
            [
                file_get_contents("{$schemaDir}/methods-mtproto.json") ?: '',
                file_get_contents("{$schemaDir}/methods-botapi.json") ?: '',
                file_exists("{$schemaDir}/schema-manifest.json") ? file_get_contents("{$schemaDir}/schema-manifest.json") : '',
            ],
        );
    }

    public function test_schema_update_dry_run_output_reports_layer(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new SchemaUpdateCommand);

        $exit = Artisan::call('teleframe:schema-update', ['--no-fetch' => true, '--dry-run' => true]);

        self::assertSame(SchemaUpdateCommand::SUCCESS, $exit);
        self::assertMatchesRegularExpression('/stamped schema-manifest\.json layer \d+/', Artisan::output());
    }

    /**
     * CONSOLE-HONESTY SWEEP — "a command that did no work must not report success".
     *
     * THE DEFECT CLASS. The silent-loss bug found and fixed in V1.1 (Notify) and
     * V1.3 (Backup) was never swept across the other 21 command classes. The
     * shape is always the same: a run reaches its end, an injected seam or a
     * query returns empty or zero, and the command returns SUCCESS anyway — so
     * a cron, a CI step or a `&&` chain reads "done" for work that never
     * happened.
     *
     * WHAT IS ASSERTED, AND WHY IT IS NOT ALL "MUST BE FAILURE".
     *
     * A command that legitimately has nothing to do must NOT be forced to
     * fail — that would be its own lie ("no buckets registered yet" is a true
     * answer, not a fault). So each entry below declares its verdict
     * HONEST_FAIL (already non-zero when nothing was done — locked in so it
     * cannot regress) or LEGITIMATE_NOOP (zero work is the correct answer, and
     * the sweep asserts the SUCCESS *and* the printed reason, so the no-op can
     * never later be reported as if work happened).
     *
     * Every entry is driven for real through Artisan::call against the
     * container seams the commands already expose — no mocking of the
     * commands themselves.
     *
     * @return array<string, array{0: class-string, 1: string, 2: array<string, mixed>, 3: int, 4: string}>
     */
    public static function noOpHonestyProvider(): array
    {
        return [
            // ---- HONEST_FAIL: "nothing to do" must be non-zero ----
            'backfill refuses a run with no account and no peer' => [
                BackfillCommand::class, 'teleframe:backfill', [], BackfillCommand::FAILURE,
                'Both --account=<id> and --peer=<peer> are required',
            ],
            'backfill refuses account 0' => [
                BackfillCommand::class, 'teleframe:backfill', ['--account' => '0'], BackfillCommand::FAILURE,
                'Both --account=<id> and --peer=<peer> are required',
            ],
            'daemon refuses when no accounts are configured' => [
                DaemonCommand::class, 'teleframe:daemon', [], DaemonCommand::FAILURE,
                'No daemon accounts configured',
            ],
            'ingest:gate refuses a non-positive account' => [
                IngestGateCommand::class, 'teleframe:ingest:gate', ['account' => '0'], IngestGateCommand::FAILURE,
                'account must be a positive integer',
            ],
            'routing refuses account 0' => [
                RoutingModeCommand::class, 'teleframe:routing', ['account' => '0'], RoutingModeCommand::FAILURE,
                'account must be a positive integer',
            ],
            'routing refuses an unknown mode' => [
                RoutingModeCommand::class, 'teleframe:routing', ['account' => '7', '--mode' => 'nope'],
                RoutingModeCommand::FAILURE, 'unknown --mode',
            ],
            'routing refuses a half-specified peer' => [
                RoutingModeCommand::class, 'teleframe:routing',
                ['account' => '7', '--peer' => '1'], RoutingModeCommand::FAILURE,
                '--peer-id is missing',
            ],
            'app refuses an unknown action' => [
                AppCommand::class, 'teleframe:app', ['action' => 'explode'], AppCommand::FAILURE,
                'unknown action',
            ],
            'tstore refuses an unknown action' => [
                TstoreCommand::class, 'teleframe:tstore', ['action' => 'explode'], TstoreCommand::FAILURE,
                'unknown action',
            ],
            'clues fails when the ledger table is absent' => [
                CluesCommand::class, 'teleframe:clues', [], CluesCommand::FAILURE,
                'could not be read',
            ],

            // ---- LEGITIMATE_NOOP: zero work is the true answer ----
            // Asserted WITH the printed reason, so "nothing happened" can never
            // later be reported as if it had. (The `teleframe:app` no-ops need
            // the AppSupport seam bound, so they are dedicated tests below.)
            'tstore buckets with none registered is an honest no-op' => [
                TstoreCommand::class, 'teleframe:tstore', ['action' => 'buckets'], TstoreCommand::SUCCESS,
                'No buckets registered yet',
            ],
        ];
    }

    #[DataProvider('noOpHonestyProvider')]
    public function test_command_that_did_no_work_does_not_report_success(
        string $commandClass,
        string $artisan,
        array $arguments,
        int $expectedExit,
        string $expectedMessage,
    ): void {
        $this->app->make(Kernel::class)->registerCommand(new $commandClass);

        $exit = Artisan::call($artisan, $arguments);

        self::assertSame(
            $expectedExit,
            $exit,
            sprintf(
                '%s: the no-op seam reported exit %d; the sweep expects %d.',
                $artisan,
                $exit,
                $expectedExit,
            ),
        );
        self::assertStringContainsString($expectedMessage, Artisan::output());
    }

    /**
     * `teleframe:app` resolves its runtime through the public
     * `AppSupport::SERVICE_KEY` seam, which is bound by the App provider this
     * TestCase does not load. An EMPTY registry is the honest no-op being
     * asserted, so the seam is bound to a real AppRuntime over an empty
     * AppRegistry — no command under test is mocked.
     */
    private function bindEmptyAppRuntime(): void
    {
        $this->app->instance(
            AppSupport::SERVICE_KEY,
            new AppRuntime(new AppRegistry, new NullSchedulePublisher),
        );
    }

    /** `teleframe:app` with no apps defined: zero installs, honestly reported. */
    public function test_app_register_with_no_apps_is_an_honest_no_op(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new AppCommand);
        $this->bindEmptyAppRuntime();

        $exit = Artisan::call('teleframe:app', ['action' => 'register']);

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('nothing to register', Artisan::output());
    }

    /**
     * `teleframe:app calls --install` under the shipped null driver.
     *
     * The sibling hole to the tstore one, and the same shape: the operator
     * explicitly asked to probe-install the app-call schedules, the
     * NullSchedulePublisher skipped every one of them, and the command
     * returned SUCCESS anyway — so a deploy step reads "app-call schedules
     * armed" while no schedule host will ever fire them.
     *
     * HONEST_FAIL, not LEGITIMATE_NOOP: the registry is NOT empty here (one
     * app carrying one scheduled call), so there was real work to do and it
     * did not happen. The empty-registry case is a different test, and a
     * genuinely different verdict.
     */
    public function test_app_calls_install_that_skipped_everything_must_not_report_success(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new AppCommand);
        $this->bindAppRuntimeWithOneScheduledCall();

        $exit = Artisan::call('teleframe:app', ['action' => 'calls', '--install' => true]);
        $output = Artisan::output();

        self::assertSame(
            AppCommand::FAILURE,
            $exit,
            'every app-call schedule was skipped, so SUCCESS would report work that never happened.',
        );
        self::assertStringContainsString('NOTHING ARMED', $output);
        // The table still prints: which schedule WOULD have been armed is
        // what makes the refusal actionable rather than merely negative.
        self::assertStringContainsString('app-call-echo-bot-status-ping-', $output);
        self::assertStringContainsString('skipped (null schedule driver)', $output);
    }

    /**
     * One app carrying one scheduled call, over the NullSchedulePublisher —
     * the shipped default. NOT the empty registry bindEmptyAppRuntime()
     * builds: that one makes `calls` return before the install branch is
     * ever reached, so it cannot reach the hole under test.
     */
    private function bindAppRuntimeWithOneScheduledCall(): void
    {
        $registry = new AppRegistry;
        $registry->define(AppDefinition::define(
            'echo-bot',
            AppMode::Bot,
            [],
            [AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive'])],
        ));

        $this->app->instance(
            AppSupport::SERVICE_KEY,
            new AppRuntime($registry, new NullSchedulePublisher),
        );
    }

    /**
     * `teleframe:passport flush` — the command whose work seam deletes
     * nothing, ever.
     *
     * It calls `clear('')`, and clear() is PER-NONCE: an empty string is not
     * a nonce that was ever issued, so neither shipped registry removes a
     * single key (MemoryNonceRegistry unsets a literal '' offset;
     * CacheNonceRegistry deletes prefix.''). It then printed "Nonce registry
     * flushed" and returned SUCCESS — so an operator who ran it to drop
     * outstanding login nonces is told the registry is clean while every
     * issued nonce is still live and still consumable.
     *
     * HONEST_FAIL, not LEGITIMATE_NOOP: this is not "there was genuinely
     * nothing to do". The command cannot know whether the registry was empty
     * (no registry exposes a count), so it cannot honestly claim the empty
     * case — and reporting success for a sweep that provably performed no
     * deletion is the defect itself.
     */
    public function test_passport_flush_that_deleted_nothing_must_not_report_success(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new PassportCommand);
        $this->app->instance(PassportSupport::NONCES_KEY, new MemoryNonceRegistry);

        $exit = Artisan::call('teleframe:passport', ['action' => 'flush']);
        $output = Artisan::output();

        self::assertSame(
            PassportCommand::FAILURE,
            $exit,
            'clear("") matches no issued nonce, so SUCCESS claims a flush that deleted nothing.',
        );
        self::assertStringContainsString('deleted nothing', $output);
    }

    /**
     * `teleframe:backup prune` while ANOTHER BACKUP HOLDS THE SET LOCK.
     *
     * Pruner::pruneReportingLock() returns scanned:0 / pruned:0 without
     * walking a single entry, so the command printed a healthy-looking
     * `prune set=default {scanned:0, pruned:0}` and returned SUCCESS. A
     * cron that shells out to prune reads "retention is keeping up" for a
     * retention pass that never ran.
     *
     * HONEST_FAIL, not LEGITIMATE_NOOP: the work was requested and refused.
     * Contrast the lock-UNAVAILABLE case (Redis down), where the prune does
     * proceed and the zero is a real count — that one stays SUCCESS, and is
     * what makes the held-lock case a distinct verdict rather than a blanket
     * "pruned 0 means failure".
     */
    public function test_backup_prune_skipped_by_a_held_lock_must_not_report_success(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new BackupCommand);
        $this->app->config->set('teleframe.backup', [
            'driver' => 'memory',
            'allow_volatile_vault' => true,
            'sets' => ['default' => ['paths' => [], 'excludes' => []]],
        ]);
        $this->app->instance(
            BackupCommand::VAULT_FACTORY_KEY,
            fn (string $setId): VaultInterface => $this->scriptedBackupVault(),
        );

        // Every SET … NX fails, so acquireSetLock() reports acquired=false /
        // unavailable=false — the "skip the prune" verdict.
        BackupRunner::setLockConnection(new HeldBackupLockConnection);

        try {
            $exit = Artisan::call('teleframe:backup', ['action' => 'prune']);
            $output = Artisan::output();

            self::assertSame(
                BackupCommand::FAILURE,
                $exit,
                'the prune was skipped wholesale (scanned:0 without a walk), so SUCCESS reports retention that never ran.',
            );
            self::assertStringContainsString('was NOT run', $output);
            self::assertStringContainsString('holds the set lock', $output);
        } finally {
            BackupRunner::setLockConnection(null);
        }
    }

    /**
     * One manifest and no chunk entries: the vault is the ONLY thing that
     * decides whether the prune has anything to do, so it must be real
     * enough for Pruner to walk. Inline (not a shared fake) so the console
     * surface under test is the whole seam.
     */
    private function scriptedBackupVault(): VaultInterface
    {
        $manifest = [
            'set' => 'default',
            'generation' => 1,
            'created' => gmdate('c'),
            'files' => [['path' => 'a.txt', 'size' => 5]],
            'chunk_hashes' => [],
        ];

        return new class($manifest) implements VaultInterface
        {
            /** @param array<string, mixed> $manifest */
            public function __construct(private readonly array $manifest) {}

            public function putChunk(string $hash, string $bytes): string
            {
                return $hash;
            }

            public function getChunk(string $hash): string
            {
                return '';
            }

            public function putManifest(string $json): string
            {
                return '0';
            }

            public function getLatestManifest(): ?array
            {
                return $this->manifest;
            }

            /**
             * @param  array<string, mixed>|null  $unreadableIds
             * @return list<array<string, mixed>>
             */
            public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
            {
                $unreadableIds = [];

                return [[
                    'generation' => 1,
                    'created' => (string) $this->manifest['created'],
                    'files' => 1,
                    'chunks' => 0,
                    'bytes' => 5,
                    'restorable' => true,
                    'missing_chunks' => 0,
                ]];
            }

            /** @return list<array{generation: int, created: string, chunk_hashes: list<string>}> */
            public function listManifestChunkHashes(int $generations = 2): array
            {
                return [[
                    'generation' => 1,
                    'created' => (string) $this->manifest['created'],
                    'chunk_hashes' => [],
                ]];
            }

            /** @return list<array<string, mixed>> */
            public function findMessagesByName(string $namePrefix): array
            {
                return [];
            }

            public function delete(string $name): void {}
        };
    }

    /** `teleframe:app list` over an empty registry: the empty table is the answer. */
    public function test_app_list_with_no_apps_is_an_honest_no_op(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new AppCommand);
        $this->bindEmptyAppRuntime();

        $exit = Artisan::call('teleframe:app', ['action' => 'list']);

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('No apps defined', Artisan::output());
    }

    /**
     * `teleframe:supervise` asked for a process that does not exist.
     *
     * The shipped catalog always has names to fall back on, so "no processes"
     * is only reachable by NAMING one that is not there — which is the seam
     * that matters anyway: the operator asked to supervise work, nothing can
     * be supervised, and the command must say so rather than come back 0.
     */
    public function test_supervise_with_an_unknown_process_refuses(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new SuperviseCommand);

        $exit = Artisan::call('teleframe:supervise', ['--process' => ['no-such-process']]);

        self::assertSame(SuperviseCommand::FAILURE, $exit);
        self::assertStringContainsString('Unknown supervised process', Artisan::output());
    }

    /**
     * `teleframe:doctor` with no credentials and a NON-INTERACTIVE input.
     *
     * Driven through a non-interactive input rather than Artisan::call,
     * because the interactive branch PROMPTS for the missing api id/hash
     * (DoctorCommand.php:62-63) and an unanswered prompt aborts the test
     * before the verdict is ever reached. Non-interactive is also the honest
     * shape for a cron or CI run, which is exactly who this exit code serves.
     */
    public function test_doctor_without_credentials_refuses(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new DoctorCommand);
        $this->app->config->set('teleframe.api_id', 0);
        $this->app->config->set('teleframe.api_hash', '');

        $command = $this->app->make(Kernel::class)->all()['teleframe:doctor'];
        $buffer = new BufferedOutput;

        $input = new ArrayInput([], $command->getDefinition());
        $input->setInteractive(false);

        $exit = $command->run($input, $buffer);

        self::assertSame(DoctorCommand::FAILURE, $exit);
        self::assertStringContainsString('needs TELEGRAM_API_ID', $buffer->fetch());
    }

    /**
     * `teleframe:vault-use-default` pointed at an account that does not
     * exist. Runs against the real migrated `telegram_accounts` table, so
     * the refusal is the command's own "no such account" verdict rather than
     * a missing-table crash — the two must not be conflated.
     */
    public function test_vault_use_default_refuses_an_unknown_account(): void
    {
        $this->migrateVaultTables();
        $this->app->make(Kernel::class)->registerCommand(new VaultUseDefaultCommand);

        $exit = Artisan::call('teleframe:vault-use-default', ['idOrLabel' => '999']);

        self::assertSame(VaultUseDefaultCommand::FAILURE, $exit);
        self::assertStringContainsString('no vault account found', Artisan::output());
    }

    private function migrateVaultTables(): void
    {
        foreach ([
            '2026_09_09_000200_create_telegram_apps_table.php',
            '2026_09_09_000201_create_telegram_accounts_table.php',
        ] as $migration) {
            $this->artisan('migrate', [
                '--path' => dirname(__DIR__, 3).'/src/Laravel/Migrations/'.$migration,
                '--realpath' => true,
            ])->assertExitCode(0);
        }
    }

    /**
     * The one command in the sweep that DID report success while having done
     * nothing — `teleframe:login` when the operator declines the .env prompt
     * and gives no --account=LABEL.
     *
     * The session is then written nowhere, and the default output path
     * deliberately does not print it, so the auth key dies with the process.
     * The command USED to return SUCCESS there, which a provisioning script
     * reads as "the account is ready". It now returns FAILURE.
     *
     * Reaches the protected seam offline via the repo's closure-bind trick
     * (the same one used for PollCommand::displayUpdateSummary above), driving
     * `confirm()` down its "no" branch with a non-interactive input whose
     * answer is therefore the caller's choice to make explicit.
     */
    public function test_login_declining_every_store_must_not_exit_success(): void
    {
        $this->app->make(Kernel::class)->registerCommand(new LoginCommand);

        $command = $this->app->make(Kernel::class)->all()['teleframe:login'];
        $exit = null;

        (function () use (&$exit): void {
            // The input carries the command's REAL definition, because
            // storeSessionInVault() reads option('account') off it, and it is
            // interactive with a one-answer stream so confirm() genuinely
            // receives "no" rather than silently falling back to its default.
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, "no\n");
            rewind($stream);

            $input = new ArrayInput([], $this->getDefinition());
            $input->setStream($stream);
            $input->setInteractive(true);

            $buffer = new BufferedOutput;
            $this->input = $input;
            $this->output = new OutputStyle($input, $buffer);
            $this->components = new Factory($buffer);

            // No --account=LABEL => storeSessionInVault() returns false at the
            // empty-label branch, so BOTH stores miss.
            $exit = $this->finalizeLogin(
                new SessionData(dcId: 2, authKey: str_repeat('x', 32)),
                'TELEGRAM_USER_SESSION',
                'User Account',
            );
        })->call($command);

        self::assertNotSame(
            LoginCommand::SUCCESS,
            $exit,
            'a session stored in NEITHER .env NOR the vault is lost with the process; SUCCESS is a silent loss.',
        );
        self::assertSame(LoginCommand::FAILURE, $exit);
    }
}

/**
 * A lock connection that is ALWAYS held, standing in for an in-flight
 * backup: `SET … NX` fails, so BackupRunner::acquireSetLock() reports
 * acquired=false / unavailable=false — the "skip the prune" verdict, which
 * is distinct from the Redis-unavailable one the same class shape produces.
 */
final class HeldBackupLockConnection extends PhpRedisConnection
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
