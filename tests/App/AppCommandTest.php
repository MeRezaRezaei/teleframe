<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Console\AppCommand;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * `teleframe:app` — the CLI shell over AppRuntime (design 2026-09-24
 * §2/§7): register (re-register defined apps onto the SHIPPED handler
 * pipeline), list (registry rows), calls (installSpec preview /
 * --install probe-install through the schedule seam), run (one scheduled
 * call now through the transport scope). Every dependency comes from the
 * container through AppSupport::SERVICE_KEY; a faked transport scope is
 * wired so the run probe dials nothing. Failure paths → FAILURE + error
 * output (TstoreCommand/NotifyCommand posture).
 */
final class AppCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        HandlerRegistry::reset();

        $this->app->make(Kernel::class)->registerCommand(new AppCommand);

        $this->app->config->set('teleframe.app.transport', static fn (AppMode $mode): CliScope => new CliScope);
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [AppServiceProvider::class];
    }

    /** The seeded echo-bot app (a trigger + one scheduled MTProto call). */
    private function seedEchoBot(string $mode = 'user', string $method = 'messages.sendMessage'): void
    {
        $this->app->config->set('teleframe.app.apps', [[
            'name' => 'echo-bot',
            'mode' => $mode,
            'triggers' => [
                ['match' => 'updateNewMessage', 'handler' => static function (Update $u): void {}, 'priority' => 0, 'on_own' => false],
            ],
            'calls' => [
                ['name' => 'status-ping', 'interval_seconds' => 3600, 'method' => $method, 'params' => ['message' => 'alive']],
            ],
        ]]);
    }

    public function test_unknown_action_returns_failure(): void
    {
        $exit = Artisan::call('teleframe:app', ['action' => 'explode']);
        $out = Artisan::output(); // output() drains — capture once, assert against $out

        self::assertSame(AppCommand::FAILURE, $exit);
        self::assertStringContainsString('unknown action "explode"', $out);
        self::assertStringContainsString('register|list|calls|run', $out);
    }

    public function test_list_reports_empty_when_no_apps_are_defined(): void
    {
        $exit = Artisan::call('teleframe:app', ['action' => 'list']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('No apps defined', $out);
    }

    public function test_register_registers_the_seeded_app_onto_the_pipeline(): void
    {
        $this->seedEchoBot();

        $exit = Artisan::call('teleframe:app', ['action' => 'register']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('Registered 1 app(s): echo-bot', $out);
        self::assertStringContainsString('tag app:{name}', $out);

        $rows = HandlerRegistry::instance()->all();
        self::assertCount(1, $rows);
        self::assertSame('app:echo-bot', $rows[0]->tag);
    }

    public function test_list_shows_the_registry_rows(): void
    {
        $this->seedEchoBot();

        $exit = Artisan::call('teleframe:app', ['action' => 'list']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('echo-bot', $out);
        self::assertStringContainsString('user', $out);
        self::assertStringContainsString('triggers', $out);
    }

    public function test_calls_previews_the_install_spec(): void
    {
        $this->seedEchoBot();

        $exit = Artisan::call('teleframe:app', ['action' => 'calls']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('app-call-echo-bot-status-ping-', $out);
        self::assertStringContainsString('telegram.app.run-call echo-bot status-ping', $out);
        self::assertStringContainsString('InstallSpec preview', $out);
    }

    public function test_calls_install_probes_through_the_null_schedule_seam(): void
    {
        $this->seedEchoBot();

        $exit = Artisan::call('teleframe:app', ['action' => 'calls', '--install' => true]);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('app-call-echo-bot-status-ping-', $out);
        self::assertStringContainsString('skipped (null schedule driver)', $out);
        self::assertStringContainsString('Probe-installed 1 app-call schedule(s)', $out);
    }

    public function test_run_invokes_the_transport_scope_and_reports_success(): void
    {
        $this->seedEchoBot();

        $exit = Artisan::call('teleframe:app', [
            'action' => 'run',
            '--app' => 'echo-bot',
            '--call' => 'status-ping',
        ]);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::SUCCESS, $exit);
        // The default probe output is the SHAPE of the result, not its values.
        self::assertStringContainsString('result: array{_=string(', $out);
        self::assertStringContainsString('Ran call status-ping on app echo-bot', $out);
        self::assertStringContainsString('--raw', $out);
    }

    /**
     * A call result is whatever the app's method returns on the wire — a chat
     * history, an auth.exportLoginToken, anything. The probe must not print
     * it, and must certainly not var_export() it (that renders object
     * internals, credentials included).
     */
    public function test_run_never_prints_the_result_values_by_default(): void
    {
        $this->seedEchoBot();
        $this->app->config->set('teleframe.app.transport', static fn (AppMode $mode): CliScope => new CliScope([
            '_' => 'auth.loginToken',
            'token' => 'AQBsuperSecretLoginTokenValue==',
            'expires' => 1_800_000_000,
            'messages' => [['text' => 'my password is hunter2']],
        ]));

        $exit = Artisan::call('teleframe:app', [
            'action' => 'run',
            '--app' => 'echo-bot',
            '--call' => 'status-ping',
        ]);
        $out = Artisan::output();

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringNotContainsString('AQBsuperSecretLoginTokenValue', $out);
        self::assertStringNotContainsString('hunter2', $out);
        self::assertStringContainsString('token=string(31)', $out, 'the field is named, the value is not');
        self::assertStringContainsString('messages=array(1)', $out);
    }

    public function test_run_raw_is_the_explicit_opt_in_for_the_whole_result(): void
    {
        $this->seedEchoBot();
        $this->app->config->set('teleframe.app.transport', static fn (AppMode $mode): CliScope => new CliScope([
            '_' => 'messages.sentMessage',
            'text' => 'alive',
        ]));

        $exit = Artisan::call('teleframe:app', [
            'action' => 'run',
            '--app' => 'echo-bot',
            '--call' => 'status-ping',
            '--raw' => true,
        ]);
        $out = Artisan::output();

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('"text": "alive"', $out);
    }

    public function test_run_raw_never_var_exports_an_unencodable_result(): void
    {
        $this->seedEchoBot();
        $this->app->config->set('teleframe.app.transport', static fn (AppMode $mode): CliScope => new CliScope([
            'resource' => STDIN, // json_encode fails on a resource
        ]));

        $exit = Artisan::call('teleframe:app', [
            'action' => 'run',
            '--app' => 'echo-bot',
            '--call' => 'status-ping',
            '--raw' => true,
        ]);
        $out = Artisan::output();

        self::assertSame(AppCommand::SUCCESS, $exit);
        self::assertStringContainsString('not JSON-encodable', $out);
        self::assertStringNotContainsString('resource(', $out);
        self::assertStringNotContainsString('stream', strtolower($out));
    }

    public function test_run_without_app_and_call_returns_failure(): void
    {
        $exit = Artisan::call('teleframe:app', ['action' => 'run']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::FAILURE, $exit);
        self::assertStringContainsString('run needs --app= and --call=', $out);
    }

    public function test_run_unknown_app_returns_failure(): void
    {
        $this->seedEchoBot();

        $exit = Artisan::call('teleframe:app', [
            'action' => 'run',
            '--app' => 'nope',
            '--call' => 'status-ping',
        ]);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::FAILURE, $exit);
        self::assertStringContainsString('Unknown app [nope]', $out);
    }

    public function test_run_mode_mismatched_call_returns_failure(): void
    {
        // bot-mode app carrying an MTProto catalog method → mode gate refuses loud.
        $this->seedEchoBot(mode: 'bot', method: 'messages.sendMessage');

        $exit = Artisan::call('teleframe:app', [
            'action' => 'run',
            '--app' => 'echo-bot',
            '--call' => 'status-ping',
        ]);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::FAILURE, $exit);
        self::assertStringContainsString('not usable by a bot-mode app', $out);
    }
}

/**
 * Offline transport scope for the run probe: dials nothing, records the
 * catalog surface contract (public call(string, array): array).
 */
final class CliScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** @param array<string, mixed>|null $answer the canned call result */
    public function __construct(private readonly ?array $answer = null) {}

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        return $this->answer ?? ['_' => $method, 'result' => 'ok'];
    }
}
