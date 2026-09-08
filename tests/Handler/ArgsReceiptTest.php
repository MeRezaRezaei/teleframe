<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\MultipleSscanfTokensException;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * Spec 5a §2: sscanf argument receipt. A single ``%s`` token in the match
 * pattern routes the captured constructor segment to the handler; more than
 * one token is rejected at registration; first-match-wins still applies.
 */
final class ArgsReceiptTest extends TestCase
{
    public function test_sscanf_arg_lands_in_handler_parameter(): void
    {
        $seen = null;
        $registry = new HandlerRegistry();
        $registry->on('/start %s', function (Update $u, string $arg) use (&$seen): void {
            $seen = $arg;
        });

        $dispatcher = new UpdateDispatcher($registry, new Pipeline(), new ArrayContainer(), new ArrayCache());
        $dispatcher->dispatch(Update::fromBus(['_' => '/start 42'], 7, 100));

        self::assertSame('42', $seen);
    }

    public function test_pattern_with_two_sscanf_tokens_throws_at_registration(): void
    {
        $this->expectException(MultipleSscanfTokensException::class);

        $registry = new HandlerRegistry();
        $registry->on('/start %s %s', fn () => null);
    }

    public function test_first_match_wins_with_args_still_holds(): void
    {
        $seen = null;
        $registry = new HandlerRegistry();
        $registry
            ->on('/start %s', function (Update $u, string $arg) use (&$seen): void {
                $seen = $arg;
            })
            ->onMessage(function (Update $u, string $arg) use (&$seen): void {
                $seen = 'catch-all';
            });

        $dispatcher = new UpdateDispatcher($registry, new Pipeline(), new ArrayContainer(), new ArrayCache());
        $dispatcher->dispatch(Update::fromBus(['_' => '/start 42'], 7, 100));

        self::assertSame('42', $seen);
    }

    public function test_matcher_carries_extracted_args(): void
    {
        $registry = new HandlerRegistry();
        $registry->on('/start %s', 'App\\StartHandler');

        $match = (new HandlerMatcher($registry))->match('/start 42');

        self::assertNotNull($match);
        self::assertSame(['42'], $match->args);
        self::assertSame('42', $match->arg());
        self::assertSame('App\\StartHandler', $match->handler->handler);
    }

    public function test_exact_and_prefix_patterns_carry_no_args(): void
    {
        $registry = new HandlerRegistry();
        $registry->on('updateNew*', 'App\\PrefixHandler');

        self::assertSame([], (new HandlerMatcher($registry))->match('updateNewMessage')?->args);
    }

    public function test_sscanf_arg_with_no_capture_does_not_match(): void
    {
        $seen = null;
        $registry = new HandlerRegistry();
        $registry->on('/start %s', function (Update $u, string $arg) use (&$seen): void {
            $seen = $arg;
        });

        $dispatcher = new UpdateDispatcher($registry, new Pipeline(), new ArrayContainer(), new ArrayCache());
        $dispatcher->dispatch(Update::fromBus(['_' => '/help'], 7, 100));

        self::assertNull($seen);
    }
}