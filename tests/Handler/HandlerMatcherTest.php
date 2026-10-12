<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use PHPUnit\Framework\TestCase;

final class HandlerMatcherTest extends TestCase
{
    private HandlerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry();
        $this->registry
            ->on('updateNewMessage', 'Exact')
            ->on('updateNew*', 'Prefix')
            ->on('*', 'CatchAll');
    }

    public function test_exact_match_wins_over_isomorphic_patterns(): void
    {
        $registry = new HandlerRegistry();
        $registry->on('updateNewMessage', 'First');
        $registry->on('updateNewMessage', 'Second');

        $matcher = new HandlerMatcher($registry);

        self::assertSame('First', $matcher->match('updateNewMessage')?->handler->handler);
    }

    public function test_prefix_pattern(): void
    {
        $matcher = new HandlerMatcher($this->registry);

        self::assertSame('Prefix', $matcher->match('updateNewChannelUser')?->handler->handler);
    }

    public function test_exact_beats_prefix(): void
    {
        $matcher = new HandlerMatcher($this->registry);

        self::assertSame('Exact', $matcher->match('updateNewMessage')?->handler->handler);
    }

    public function test_catch_all_only_when_nothing_else_matches(): void
    {
        $matcher = new HandlerMatcher($this->registry);

        self::assertSame('CatchAll', $matcher->match('somethingElse')?->handler->handler);
        self::assertSame('CatchAll', $matcher->match('')?->handler->handler);
    }

    public function test_priority_beats_registration_order(): void
    {
        $registry = new HandlerRegistry();
        $registry
            ->on('*', 'First', 1)
            ->on('*', 'Second', 5);

        $matcher = new HandlerMatcher($registry);

        self::assertSame('Second', $matcher->match('updateNewMessage')?->handler->handler);
    }

    public function test_no_match_returns_null(): void
    {
        $registry = new HandlerRegistry();
        $registry->on('updateNew*', fn () => null);

        self::assertNull((new HandlerMatcher($registry))->match('updateEditMessage'));
    }
}