<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Laravel\Console\LoginCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\PollCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaUpdateCommand;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
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
}
