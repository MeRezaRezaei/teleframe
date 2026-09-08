<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\TelegramContext;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * The onion + the one dispatch point. These tests drive the REAL pipeline —
 * no handler-substrate stub (phase gate). Just transport is faked.
 */
final class DispatcherTest extends TestCase
{
    private HandlerRegistry $registry;
    private Pipeline $pipeline;
    private ArrayContainer $container;
    private ArrayCache $sends;
    private UpdateDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry();
        $this->pipeline = new Pipeline();
        $this->container = new ArrayContainer();
        $this->sends = new ArrayCache();
        $this->dispatcher = new UpdateDispatcher(
            $this->registry,
            $this->pipeline,
            $this->container,
            $this->sends,
        );
    }

    public function test_pipeline_runs_middleware_in_order_and_terminal_last(): void
    {
        $trace = [];
        $pipeline = new Pipeline();
        $runner = $pipeline->then(
            [
                function (Update $u, callable $next) use (&$trace) {
                    $trace[] = 'a';
                    return $next($u);
                },
                function (Update $u, callable $next) use (&$trace) {
                    $trace[] = 'b';
                    return $next($u);
                },
            ],
            function (Update $u) use (&$trace) {
                $trace[] = 't';
                return 'done';
            },
        );

        $result = $runner(new Update(['_' => 'x'], 1));
        $trace[] = 'after';

        self::assertSame(['a', 'b', 't', 'after'], $trace);
        self::assertSame('done', $result);
    }

    public function test_pipeline_short_circuit_skips_terminal(): void
    {
        $ran = false;
        $runner = (new Pipeline())->then(
            [
                fn (Update $u, callable $next) => null,
            ],
            function (Update $u) use (&$ran) {
                $ran = true;
            },
        );

        $runner(new Update(['_' => 'x'], 1));

        self::assertFalse($ran);
    }

    public function test_dispatch_runs_matched_handler_with_update(): void
    {
        $seen = null;
        $this->registry->on('updateNewMessage', function (Update $update) use (&$seen): void {
            $seen = $update;
        });

        $update = Update::fromBus(['_' => 'updateNewMessage'], 123);
        $this->dispatcher->dispatch($update);

        self::assertSame($update, $seen);
    }

    public function test_dispatch_no_match_is_noop(): void
    {
        $ran = false;
        $this->registry->on('updateNewMessage', function () use (&$ran): void {
            $ran = true;
        });

        $this->dispatcher->dispatch(new Update(['_' => 'somethingElse'], 1));

        self::assertFalse($ran);
    }

    public function test_handler_receives_container_bound_context(): void
    {
        $context = null;
        $this->registry->onMessage(function (Update $u, TelegramContext $ctx) use (&$context): void {
            $context = $ctx;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x'], 55));

        self::assertNotNull($context);
        self::assertSame(55, $context->accountId());
    }

    public function test_pair_handler_is_resolved_from_container(): void
    {
        $handler = new class {
            public array $calls = [];
            public function handle(Update $update): void
            {
                $this->calls[] = $update->constructor();
            }
        };

        $this->container->set(TestHandler::class, $handler);
        $this->registry->on('updateNewMessage', [TestHandler::class, 'handle']);

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'updateNewMessage'], 1));

        self::assertSame(['updateNewMessage'], $handler->calls);
    }

    public function test_untyped_parameter_receives_update(): void
    {
        $seen = null;
        $this->registry->onMessage(function ($frame) use (&$seen): void {
            $seen = $frame;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'z'], 1));

        self::assertInstanceOf(Update::class, $seen);
    }
}

final class TestHandler
{
    public function handle(Update $update): void {}
}