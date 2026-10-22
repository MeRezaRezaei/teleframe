<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * END-TO-END app dispatch (design §1/§4, gap G3): an AppMode::Bot app whose
 * trigger closure reads `$u->array['message']['text']` for '/ping' is
 * registered via AppRuntime (registry define + AppResponder install on the
 * SHIPPED shared hot table) and driven with ONE fixture update through the
 * REAL pipeline — real HandlerRegistry, real HandlerMatcher, real
 * EchoEliminator onion, real UpdateDispatcher invoke. The closure records
 * the matched text into a captured-by-ref var (it does NOT actually send —
 * no chat, no credentials); the fake dispatcher's facade send path is
 * asserted untouched.
 */
final class FakeDispatcherAppTest extends TestCase
{
    private HandlerRegistry $registry;

    private ArrayContainer $container;

    private ArrayCache $sends;

    protected function setUp(): void
    {
        HandlerRegistry::reset();
        $this->registry = HandlerRegistry::instance();
        $this->container = new ArrayContainer;
        $this->sends = new ArrayCache;
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();
    }

    private static function handler(): \Closure
    {
        return static function (Update $u): void {};
    }

    public function test_app_trigger_runs_through_the_real_pipeline_and_records_the_reply(): void
    {
        $seen = [];

        $trigger = AppTrigger::on('updateNewMessage', function (Update $u) use (&$seen): void {
            $text = (string) ($u->array['message']['text'] ?? '');
            if ($text === '/ping') {
                $seen[] = $text; // recorded — deliberately NOT sent
            }
        });

        $app = AppDefinition::define('echo-bot', AppMode::Bot, [$trigger], [
            AppCall::every('status-ping', 3600, 'sendMessage', ['chat_id' => '@ops', 'text' => 'alive']),
        ]);

        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: new NullSchedulePublisher,
            transport: static fn (AppMode $mode): AppTransportResolverStub => new AppTransportResolverStub(),
        );
        $runtime->register($app);

        // Tag visible on the shared hot table.
        $rows = $this->registry->all();
        self::assertCount(1, $rows);
        self::assertSame('updateNewMessage', $rows[0]->match);
        self::assertSame('app:echo-bot', $rows[0]->tag);

        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage', 'message' => ['text' => '/ping']], 'account_id' => 7]],
            $this->registry,
            $this->container,
            $this->sends,
        );

        $result = $fake->run();

        // The callback RAN through the real matcher + pipeline and recorded.
        self::assertSame(['/ping'], $seen);
        self::assertCount(
            1,
            $result['dispatched'],
            'the one updateNewMessage the fake fed in was dispatched exactly once',
        );
        self::assertSame([], $result['sent'], 'the app closure records the reply instead of sending');
        self::assertSame(7, $result['dispatched'][0]->accountId);
    }

    public function test_non_matching_constructor_never_invokes_the_app_handler(): void
    {
        $seen = [];

        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: new NullSchedulePublisher,
            transport: static fn (AppMode $mode): AppTransportResolverStub => new AppTransportResolverStub(),
        );
        $runtime->register(AppDefinition::define('echo-bot', AppMode::Bot, [
            AppTrigger::on('updateNewMessage', function (Update $u) use (&$seen): void {
                $seen[] = true;
            }),
        ]));

        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewChannel', 'pts' => 1], 'account_id' => 7]],
            $this->registry,
            $this->container,
            $this->sends,
        );

        $fake->run();

        self::assertSame([], $seen);
        self::assertCount(0, $fake->dispatched);
    }

    public function test_app_handlers_are_subject_to_the_real_echo_eliminator(): void
    {
        $seen = [];

        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: new NullSchedulePublisher,
            transport: static fn (AppMode $mode): AppTransportResolverStub => new AppTransportResolverStub(),
        );
        $runtime->register(AppDefinition::define('echo-bot', AppMode::Bot, [
            AppTrigger::on('updateNewMessage', function (Update $u) use (&$seen): void {
                $seen[] = true;
            }),
        ]));

        // The update IS the echo of a framework send: the real eliminator
        // terminates the chain before any app handler runs.
        $eliminator = new EchoEliminator($this->sends, new HandlerMatcher($this->registry));
        $eliminator->remember(7, ['random_id' => 'app-echo-1']);

        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage', 'random_id' => 'app-echo-1'], 'account_id' => 7]],
            $this->registry,
            $this->container,
            $this->sends,
        );

        $fake->run();

        self::assertSame([], $seen);
        self::assertCount(0, $fake->dispatched);
    }
}

/**
 * A transport resolver stub for the dispatch-only tests: dispatch never
 * dials the transport (only runCall does), but the seam must be a public
 * call(string, array): array scope-shaped object, so a trivial one is
 * injected instead of the loud default.
 */
final class AppTransportResolverStub
{
    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        return ['_' => $method];
    }
}
