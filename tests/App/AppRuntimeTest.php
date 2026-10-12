<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * AppRuntime (design §2/§9) — THE orchestrator: register() defines in the
 * registry AND installs the triggers onto the shared handler pipeline;
 * installCalls() composes AppCallSpec payloads onto the SchedulePublisher
 * seam (fake publisher records, null publisher reports 'skipped');
 * runCall() gates the catalog method against the app's own mode (bot ↔
 * bot-http, user ↔ mtproto — mismatches and unknown methods refuse LOUD),
 * resolves the transport at call time and invokes call(string, array).
 * Failures log at `error` ('telegram.app.run-call.failed') and ALWAYS
 * rethrow the original unchanged.
 */
final class AppRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        HandlerRegistry::reset();
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();
    }

    private static function handler(): \Closure
    {
        return static function (Update $u): void {};
    }

    private static function fakeTransport(string $return = 'ok'): \Closure
    {
        return static function (AppMode $mode) use ($return): RecordingScope {
            return new RecordingScope($return);
        };
    }

    public function test_register_defines_in_the_registry_and_installs_triggers_on_the_pipeline(): void
    {
        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        $app = AppDefinition::define('echo-bot', AppMode::Bot, [
            AppTrigger::on('updateNewMessage', self::handler()),
            AppTrigger::on('*', self::handler(), priority: 5),
        ], [AppCall::every('status-ping', 3600, 'sendMessage')]);

        $name = $runtime->register($app);

        self::assertSame('echo-bot', $name);
        self::assertTrue($runtime->registry()->has('echo-bot'));
        self::assertSame($app, $runtime->registry()->resolve('echo-bot'));
        self::assertSame(2, HandlerRegistry::instance()->count(), 'both triggers installed onto the shared hot table');
        self::assertSame('app:echo-bot', HandlerRegistry::instance()->all()[0]->tag);
    }

    public function test_install_calls_records_payloads_through_a_fake_publisher(): void
    {
        $publisher = new RecordingSchedulePublisher;
        $app = self::app('echo-bot', calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
        ]);

        $runtime = new AppRuntime(
            registry: self::registryWith($app),
            schedules: $publisher,
            transport: self::fakeTransport(),
        );

        $status = $runtime->installCalls();

        $callId = (new AppCallSpec)->callId($app, $app->calls()[0]);

        self::assertSame([$callId => 'installed'], $status);
        self::assertSame([$callId], array_keys($publisher->installed));
        self::assertSame([
            'interval_seconds' => 3600,
            'activity' => 'telegram.app.run-call echo-bot status-ping',
            'app' => 'echo-bot',
            'call' => 'status-ping',
        ], $publisher->installed[$callId]);
    }

    public function test_install_calls_arms_one_schedule_per_app_call_across_apps(): void
    {
        // installCalls() merges the per-app payloads with `+=`, so a schedule id
        // shared between two apps silently DROPPED the second app's call before
        // the publisher ever saw it. The id carries the app name, so nothing is
        // lost and nothing overwrites anything else.
        $publisher = new RecordingSchedulePublisher;

        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: $publisher,
            transport: self::fakeTransport(),
        );

        $runtime->register(self::app('alpha-bot', calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage'),
        ]));
        $runtime->register(self::app('beta-bot', calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage'),
        ]));

        $status = $runtime->installCalls();

        self::assertCount(2, $status, 'both apps must reach the schedule seam');
        self::assertCount(2, $publisher->installed);

        $intervals = [];
        foreach ($publisher->installed as $id => $payload) {
            $intervals[$payload['app']] = $payload['interval_seconds'];
        }

        self::assertSame(['alpha-bot' => 3600, 'beta-bot' => 60], $intervals);
    }

    public function test_install_calls_with_the_null_publisher_reports_skipped(): void
    {
        $app = self::app('echo-bot', calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage'),
        ]);

        $runtime = new AppRuntime(
            registry: self::registryWith($app),
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        $status = $runtime->installCalls();

        self::assertSame(
            [(new AppCallSpec)->callId($app, $app->calls()[0]) => 'skipped (null schedule driver)'],
            $status,
        );
    }

    public function test_run_call_invokes_the_fake_scope_and_returns_the_array(): void
    {
        $scope = new RecordingScope('array-result');

        $runtime = new AppRuntime(
            registry: self::registryWith(self::app('echo-bot', AppMode::User, calls: [
                AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops', 'message' => 'alive']),
            ])),
            schedules: new NullSchedulePublisher,
            transport: static fn (AppMode $mode): RecordingScope => $scope,
        );

        $result = $runtime->runCall('echo-bot', 'status-ping');

        self::assertSame(['_' => 'messages.sendMessage', 'result' => 'array-result'], $result);
        self::assertSame('array-result', $result['result']);
        self::assertSame([['method' => 'messages.sendMessage', 'params' => ['peer' => '@ops', 'message' => 'alive']]], $scope->calls);
        self::assertSame(1, $scope->invoked);
    }

    public function test_run_call_refuses_a_bot_api_method_on_a_user_app_and_vice_versa(): void
    {
        // bot-mode app carries an MTProto catalog method → mode-mismatch.
        $botRuntime = new AppRuntime(
            registry: self::registryWith(self::app('bot-app', AppMode::Bot, calls: [
                AppCall::every('status-ping', 3600, 'messages.sendMessage'),
            ])),
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        try {
            $botRuntime->runCall('bot-app', 'status-ping');
            self::fail('a bot-mode app must refuse an MTProto catalog method');
        } catch (AppException $e) {
            self::assertStringContainsString('messages.sendMessage', $e->getMessage());
            self::assertStringContainsString('mtproto catalog method', $e->getMessage());
            self::assertStringContainsString('bot-mode app', $e->getMessage());
        }

        // user-mode app carries a Bot API catalog method → mode-mismatch.
        $userRuntime = new AppRuntime(
            registry: self::registryWith(self::app('user-app', AppMode::User, calls: [
                AppCall::every('status-ping', 3600, 'sendMessage'),
            ])),
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        try {
            $userRuntime->runCall('user-app', 'status-ping');
            self::fail('a user-mode app must refuse a bot-http catalog method');
        } catch (AppException $e) {
            self::assertStringContainsString('sendMessage', $e->getMessage());
            self::assertStringContainsString('bot-http catalog method', $e->getMessage());
            self::assertStringContainsString('user-mode app', $e->getMessage());
        }
    }

    public function test_run_call_unknown_method_refuses_loud(): void
    {
        $runtime = new AppRuntime(
            registry: self::registryWith(self::app('echo-bot', AppMode::User, calls: [
                AppCall::every('status-ping', 3600, 'no.such.method'),
            ])),
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        try {
            $runtime->runCall('echo-bot', 'status-ping');
            self::fail('an uncatalogued method must refuse loud');
        } catch (AppException $e) {
            self::assertStringContainsString('no.such.method', $e->getMessage());
        }
    }

    public function test_run_call_unknown_app_or_call_refuses_loud(): void
    {
        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        try {
            $runtime->runCall('ghost-app', 'status-ping');
            self::fail('an unknown app must refuse loud');
        } catch (AppException $e) {
            self::assertStringContainsString('Unknown app', $e->getMessage());
        }

        $runtimeWithApp = new AppRuntime(
            registry: self::registryWith(self::app('echo-bot', calls: [AppCall::every('status-ping', 3600, 'messages.sendMessage')])),
            schedules: new NullSchedulePublisher,
            transport: self::fakeTransport(),
        );

        try {
            $runtimeWithApp->runCall('echo-bot', 'ghost-call');
            self::fail('an unknown call must refuse loud');
        } catch (AppException $e) {
            self::assertStringContainsString("app 'echo-bot' has no scheduled call 'ghost-call'", $e->getMessage());
        }
    }

    public function test_run_call_logs_error_and_rethrows_the_original_exception_unchanged(): void
    {
        $logger = new ArrayLogger;
        $original = new RuntimeException('boom: transport exploded');
        $failing = new FailingScope($original);

        $runtime = new AppRuntime(
            registry: self::registryWith(self::app('echo-bot', AppMode::User, calls: [
                AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
            ])),
            schedules: new NullSchedulePublisher,
            transport: static fn (AppMode $mode): FailingScope => $failing,
            logger: $logger,
        );

        $caught = null;
        try {
            $runtime->runCall('echo-bot', 'status-ping');
            self::fail('a failing transport scope must propagate');
        } catch (Throwable $e) {
            $caught = $e;
        }

        // Original exception rethrown unchanged — same instance, same message.
        self::assertSame($original, $caught);
        self::assertSame('boom: transport exploded', $caught->getMessage());

        self::assertCount(1, $logger->records);
        self::assertSame('error', $logger->records[0]['level']);
        self::assertSame('telegram.app.run-call.failed', $logger->records[0]['message']);
        self::assertSame('echo-bot', $logger->records[0]['context']['app']);
        self::assertSame('status-ping', $logger->records[0]['context']['call']);
        self::assertSame('messages.sendMessage', $logger->records[0]['context']['method']);
        self::assertSame('user', $logger->records[0]['context']['mode']);
        self::assertSame($original, $logger->records[0]['context']['exception']);
    }

    public function test_run_call_without_a_logger_stays_silent_and_rethrows(): void
    {
        $original = new RuntimeException('boom');
        $runtime = new AppRuntime(
            registry: self::registryWith(self::app('echo-bot', AppMode::User, calls: [
                AppCall::every('status-ping', 3600, 'messages.sendMessage'),
            ])),
            schedules: new NullSchedulePublisher,
            transport: static fn (AppMode $mode): FailingScope => new FailingScope($original),
        );

        try {
            $runtime->runCall('echo-bot', 'status-ping');
            self::fail('must rethrow even without a logger');
        } catch (Throwable $e) {
            self::assertSame($original, $e);
        }
    }

    /** One app in a registry (registry duplicate-define would otherwise refuse). */
    private static function registryWith(AppDefinition $app): AppRegistry
    {
        return (new AppRegistry)->define($app);
    }

    private static function app(string $name, AppMode $mode = AppMode::User, array $calls = []): AppDefinition
    {
        return AppDefinition::define($name, $mode, triggers: [], calls: $calls);
    }
}

/** Recording SchedulePublisher: captures install() payloads verbatim. */
final class RecordingSchedulePublisher implements SchedulePublisher
{
    /** @var array<string, array<string, mixed>> */
    public array $installed = [];

    public function install(array $schedules): array
    {
        foreach ($schedules as $id => $spec) {
            $this->installed[(string) $id] = (array) $spec;
        }

        return array_fill_keys(array_keys($schedules), 'installed');
    }

    public function describe(): string
    {
        return 'recording schedule publisher (test)';
    }
}

/** Fake transport scope: spies call(string, array) and returns the stub value. */
final class RecordingScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    public int $invoked = 0;

    public function __construct(
        private readonly string $result = 'ok',
    ) {}

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];
        $this->invoked++;

        return ['_' => $method, 'result' => $this->result];
    }
}

/** Fake transport scope whose call() always throws the given exception. */
final class FailingScope
{
    public function __construct(
        private readonly Throwable $failure,
    ) {}

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        throw $this->failure;
    }
}
