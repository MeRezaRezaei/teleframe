<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\TelegramContext;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
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
        $this->registry = new HandlerRegistry;
        $this->pipeline = new Pipeline;
        $this->container = new ArrayContainer;
        $this->sends = new ArrayCache;
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
        $pipeline = new Pipeline;
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
        $runner = (new Pipeline)->then(
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

    public function test_handler_exception_is_logged_then_rethrown(): void
    {
        $logger = new ArrayLogger;
        $this->dispatcher = new UpdateDispatcher(
            $this->registry,
            $this->pipeline,
            $this->container,
            $this->sends,
            logger: $logger,
        );

        $this->registry->on('updateNewMessage', function (Update $update): void {
            throw new \RuntimeException('handler boom');
        });

        try {
            $this->dispatcher->dispatch(Update::fromBus(['_' => 'updateNewMessage'], 123));
            self::fail('expected the handler exception to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('handler boom', $e->getMessage());
        }

        self::assertCount(1, $logger->records);
        self::assertSame('error', $logger->records[0]['level']);
        self::assertSame('teleframe.dispatch.failed', $logger->records[0]['message']);
        self::assertSame('updateNewMessage', $logger->records[0]['context']['constructor']);
        self::assertSame(123, $logger->records[0]['context']['account']);
        self::assertSame('handler boom', $logger->records[0]['context']['error']);
        self::assertSame(\RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    public function test_dispatch_is_silent_with_default_null_logger(): void
    {
        $this->registry->on('updateNewMessage', function (Update $update): void {
            throw new \RuntimeException('propagates despite the NullLogger');
        });

        try {
            $this->dispatcher->dispatch(Update::fromBus(['_' => 'updateNewMessage'], 1));
            self::fail('expected the handler exception to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('propagates despite the NullLogger', $e->getMessage());
        }
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
        $handler = new class
        {
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

    // ── builtin parameter coercion (friction audit: raw TL objects must
    // ── never be handed to scalar-typed params) ─────────────────────────

    public function test_builtin_int_param_is_coerced_from_snake_case_field(): void
    {
        $seen = null;
        $this->registry->onMessage(function (Update $u, int $userId) use (&$seen): void {
            $seen = $userId;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x', 'user_id' => 42], 7));

        self::assertSame(42, $seen);
    }

    public function test_builtin_string_param_is_coerced_from_exact_field(): void
    {
        $seen = null;
        $this->registry->onMessage(function (Update $u, string $message) use (&$seen): void {
            $seen = $message;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x', 'message' => 'hello'], 7));

        self::assertSame('hello', $seen);
    }

    public function test_builtin_bool_param_is_coerced(): void
    {
        $seen = null;
        $this->registry->onMessage(function (Update $u, bool $enabled) use (&$seen): void {
            $seen = $enabled;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x', 'enabled' => 1], 7));

        self::assertTrue($seen);
    }

    public function test_mixed_param_receives_the_raw_field_value(): void
    {
        $seen = null;
        $this->registry->onMessage(function (Update $u, mixed $payload) use (&$seen): void {
            $seen = $payload;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x', 'payload' => ['nested' => true]], 7));

        self::assertSame(['nested' => true], $seen);
    }

    public function test_builtin_arg_param_receives_the_captured_token_coerced(): void
    {
        $seen = null;
        $this->registry->on('/start %s', function (Update $u, int $arg) use (&$seen): void {
            $seen = $arg;
        });

        $this->dispatcher->dispatch(Update::fromBus(['_' => '/start 12345'], 7));

        self::assertSame(12345, $seen, 'the sscanf token must be coerced to the declared int');
    }

    public function test_missing_builtin_field_skips_param_instead_of_fataling(): void
    {
        $seen = [];
        $this->registry->onMessage(function (Update $u, int $answered = 0) use (&$seen): void {
            $seen[] = $answered;
        });

        // No `answered` field on the frame: the parameter is skipped (with
        // its default), never handed a raw Update object.
        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x'], 7));

        self::assertSame([0], $seen);
    }

    public function test_non_scalar_field_skips_scalar_param_instead_of_fataling(): void
    {
        $seen = [];
        $this->registry->onMessage(function (Update $u, int $userId = 5) use (&$seen): void {
            $seen[] = $userId;
        });

        // `user_id` carries an array — a shape that can never satisfy `int`;
        // the param is skipped, the handler still runs with its default.
        $this->dispatcher->dispatch(Update::fromBus(['_' => 'x', 'user_id' => ['nested' => true]], 7));

        self::assertSame([5], $seen);
    }

    // ── within-run forward dedup (event path — ReplayDedup skips those) ──

    public function test_event_path_repeat_of_one_mirrored_model_is_forwarded_once(): void
    {
        $ran = 0;
        $this->registry->onMessage(function (Update $u) use (&$ran): void {
            $ran++;
        });

        // ReplayDedup skips the event path (ReplayDedup.php:51), so the
        // within-run forward guard is what collapses a re-delivered row.
        $model = new TlUser(['id' => 99]);
        $update = Update::fromMirror($model, 7, 1000);

        $this->dispatcher->dispatch($update);
        $this->dispatcher->dispatch($update);

        self::assertSame(1, $ran);
    }

    /**
     * The real property the tautological test above could not see: DISTINCT
     * mirrored messages must each reach the handler, through ONE dispatcher
     * instance, even though every one of them hashes to the same updateId
     * (the event path's array is the constructor-only `['_'] => 'message'`
     * identity row, so updateId() degenerates to a per-account+constructor
     * constant — the very degeneracy ReplayDedup refuses to dedup on).
     *
     * Real `TfMessage` roots, no constructor shortcut, 500 of them.
     */
    public function test_event_path_distinct_mirrored_messages_are_each_forwarded_once(): void
    {
        $seen = [];
        $this->registry->onMessage(function (Update $u) use (&$seen): void {
            $seen[] = $u->model?->getAttribute('id');
        });

        $first = null;
        for ($id = 1; $id <= 500; $id++) {
            $model = new TfMessage;
            $model->setAttribute('account_id', 1);
            $model->setAttribute('id', $id);
            $model->setAttribute('constructor', 'message');
            $model->setAttribute('message', 'body '.$id);

            $update = Update::fromMirror($model, 1);
            $first ??= $update;

            $this->dispatcher->dispatch($update);
        }

        self::assertCount(500, $seen, 'every distinct mirrored message reaches the handler once');
        self::assertSame(range(1, 500), $seen);

        // A genuine re-delivery of ONE message is still suppressed.
        $this->dispatcher->dispatch($first);
        $this->dispatcher->dispatch($first);

        self::assertCount(500, $seen, 'a re-delivered message is forwarded once only');
    }

    // ── dead-letter seam (tag, never swallow) ───────────────────────────

    public function test_failure_dead_letters_with_reason_and_error(): void
    {
        $dead = null;
        $logger = new ArrayLogger;
        $this->dispatcher = new UpdateDispatcher(
            $this->registry,
            $this->pipeline,
            $this->container,
            $this->sends,
            logger: $logger,
            deadLetter: function (Update $update, string $reason, string $error) use (&$dead): void {
                $dead = [$update, $reason, $error];
            },
        );

        $this->registry->on('updateNewMessage', function (Update $update): void {
            throw new \RuntimeException('handler boom');
        });

        try {
            $this->dispatcher->dispatch(Update::fromBus(['_' => 'updateNewMessage'], 123));
            self::fail('expected the handler exception to propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('handler boom', $e->getMessage());
        }

        self::assertNotNull($dead, 'a failing update must reach the dead-letter seam');
        self::assertSame(UpdateDispatcher::DEAD_LETTER_REASON, $dead[1]);
        self::assertSame('handler boom', $dead[2]);
        self::assertSame(123, $dead[0]->accountId);
        self::assertCount(1, $logger->records, 'the log record and the DL tag coexist — neither swallows');
    }

    public function test_success_never_dead_letters(): void
    {
        $dead = false;
        $this->dispatcher = new UpdateDispatcher(
            $this->registry,
            $this->pipeline,
            $this->container,
            $this->sends,
            deadLetter: function () use (&$dead): void {
                $dead = true;
            },
        );

        $this->registry->on('updateNewMessage', function (Update $update): void {});

        $this->dispatcher->dispatch(Update::fromBus(['_' => 'updateNewMessage'], 1));

        self::assertFalse($dead);
    }
}

final class TestHandler
{
    public function handle(Update $update): void {}
}
