<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * THE phase gate (roadmap Phase 3): a fake uprate flows the REAL pipeline —
 * real registry, real 26-line onion, real echo eliminator, DI resolution.
 * Only transport (redis/network/mirror) is faked. If these pass, the
 * running-mode surface is honest, not a handler-substrate stub.
 */
final class FakeRunningModeTest extends TestCase
{
    private HandlerRegistry $registry;
    private ArrayContainer $container;
    private ArrayCache $sends;

    protected function setUp(): void
    {
        $this->sends = new ArrayCache();
        $this->container = new ArrayContainer();
        $this->registry = new HandlerRegistry();
    }

    public function test_fake_uprate_flows_real_pipeline_and_records_dispatch(): void
    {
        $seen = [];
        $this->registry->on('updateNewMessage', function (Update $u) use (&$seen) {
            $seen[] = $u->constructor();
        });

        $fake = new FakeDispatcher(
            [
                ['update' => ['_' => 'updateNewMessage', 'message' => 'hi'], 'account_id' => 7],
                ['update' => ['_' => 'updateNewMessage', 'message' => 'yo'], 'account_id' => 7],
            ],
            $this->registry,
            $this->container,
            $this->sends,
        );

        $result = $fake->run();

        self::assertSame(['updateNewMessage', 'updateNewMessage'], $seen);
        self::assertCount(2, $result['dispatched']);
        self::assertCount(2, $fake->dispatched);
        self::assertSame([], $result['sent']);
    }

    public function test_echo_eliminated_by_real_eliminator_in_real_pipeline(): void
    {
        $registry = new HandlerRegistry();
        $registry->on('updateNewMessage', fn () => null);
        $handlerSends = new ArrayCache();
        $eliminator = new \MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator(
            $handlerSends,
            new \MeRezaRezaei\Teleframe\Handler\HandlerMatcher($registry),
        );
        $eliminator->remember(1, ['random_id' => 'r-echo']);

        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage', 'random_id' => 'r-echo'], 'account_id' => 1]],
            $registry,
            $this->container,
            $handlerSends,
        );

        $fake->run();

        self::assertCount(0, $fake->dispatched);
    }

    public function test_priority_ordering_honored_in_real_pipeline(): void
    {
        $order = [];
        $this->registry
            ->on('*', function (Update $u) use (&$order) {
                $order[] = 'low';
            }, 1)
            ->on('*', function (Update $u) use (&$order) {
                $order[] = 'high';
            }, 5);

        (new FakeDispatcher([['update' => ['_' => 'x']]], $this->registry, $this->container, $this->sends))->run();

        self::assertSame(['high'], $order); // only the highest-priority match runs
    }

    public function test_di_resolution_happens_in_real_dispatcher(): void
    {
        $resolved = new class {
            public function __invoke(Update $update): string
            {
                return 'handled:' . $update->constructor();
            }
        };
        $this->container->set(DiHandler::class, $resolved);
        $this->registry->on('updateNewMessage', DiHandler::class);

        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage'], 'account_id' => 2]],
            $this->registry,
            $this->container,
            $this->sends,
        );

        $fake->run();

        self::assertCount(1, $fake->dispatched);
    }

    public function test_explicit_send_is_recorded_through_the_test_surface(): void
    {
        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage'], 'account_id' => 3]],
            $this->registry,
            $this->container,
            $this->sends,
        );
        $this->registry->onMessage(function (Update $u) use ($fake): void {
            $fake->send($u->accountId, ['api' => 'sendMessage', 'text' => $u->constructor()]);
        });

        $result = $fake->run();

        self::assertCount(1, $result['sent']);
        self::assertSame(3, $result['sent'][0]['account']);
    }
}

final class DiHandler
{
    public function __invoke(Update $update): string
    {
        return 'ok';
    }
}