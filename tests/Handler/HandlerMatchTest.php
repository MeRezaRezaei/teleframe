<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\Handler;
use MeRezaRezaei\Teleframe\Handler\HandlerMatch;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Spec 5a §2: the HandMatch value object. args carries the extracted sscanf
 * list, arg() is the single-token convenience, and match carries back the
 * registered Handler record.
 */
final class HandlerMatchTest extends TestCase
{
    public function test_args_list_is_exposed(): void
    {
        $registry = new HandlerRegistry;
        $registry->on('/start %s', fn () => null);

        $match = (new HandlerMatcher($registry))->match('/start 42');

        self::assertNotNull($match);
        self::assertSame(['42'], $match->args);
    }

    public function test_arg_returns_token_at_index(): void
    {
        $match = new HandlerMatch(new Handler('/ping', fn () => null), ['hello']);

        self::assertSame('hello', $match->arg());
    }

    public function test_arg_returns_null_for_missing_index(): void
    {
        $match = new HandlerMatch(new Handler('/ping', fn () => null));

        self::assertNull($match->arg());
    }

    public function test_zero_sscanf_tokens_yields_empty_args(): void
    {
        $registry = new HandlerRegistry;
        $registry->on('updateNew*', fn () => null);

        $match = (new HandlerMatcher($registry))->match('updateNewMessage');

        self::assertNotNull($match);
        self::assertSame([], $match->args);
        self::assertNull($match->arg());
    }

    /**
     * The two literals handed to `on()` are the oracle. The arrangement used
     * to build the expected `Handler` with the same constructor and the same
     * two literals, and `HandlerRegistry::on()` passes them through
     * untransformed — so a constructor that dropped or overwrote either
     * field was invisible: both sides of the comparison carried the identical
     * defect. Reading the literals straight back is the whole assertion.
     */
    public function test_carried_handler_is_the_registered_one(): void
    {
        $registry = new HandlerRegistry;
        $registry->on('/start %s', 'App\\StartHandler');

        $match = (new HandlerMatcher($registry))->match('/start 42');

        self::assertNotNull($match);
        self::assertSame('/start %s', $match->handler->match);
        self::assertSame('App\\StartHandler', $match->handler->handler);
    }
}
