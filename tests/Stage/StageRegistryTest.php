<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Stage\StageRegistry;
use MeRezaRezaei\Teleframe\Stage\StageSet;
use PHPUnit\Framework\TestCase;

/**
 * StageRegistry (Phase 5e): zero-regex constructor matching identical to the
 * HandlerMatcher grammar (exact / prefix* / * / single %s); first-match-wins;
 * set/submit resolution by exact name then pattern; compile() freezes.
 */
final class StageRegistryTest extends TestCase
{
    public function test_match_routes_by_constructor_pattern(): void
    {
        $registry = (new StageRegistry())
            ->on(StageSet::define('updateNewMessage', ['email' => ['steps' => ['email']]]))
            ->on(StageSet::define('start*', ['name' => ['steps' => ['name']]]))
            ->compile();

        self::assertNotNull($registry->match('updateNewMessage'));
        self::assertNull($registry->match('callback_query'));
        self::assertSame('start*', $registry->setFor('start')?->name());
        self::assertSame('start*', $registry->setFor('startup')?->name());
    }

    public function test_catch_all_matches_everything(): void
    {
        $registry = (new StageRegistry())
            ->on(StageSet::define('*', ['any' => ['steps' => ['any']]]))
            ->compile();

        self::assertNotNull($registry->match('updateNewMessage'));
        self::assertNotNull($registry->match('callback_query'));
        self::assertNotNull($registry->match('anything-else'));
    }

    public function test_single_percent_s_token_captures_but_must_present(): void
    {
        $registry = (new StageRegistry())
            ->on(StageSet::define('confirm %s', ['tos' => ['steps' => ['tos']]]))
            ->compile();

        self::assertNotNull($registry->match('confirm yes'));
        self::assertNull($registry->match('confirm'));
    }

    public function test_first_match_wins_in_registration_order(): void
    {
        $registry = (new StageRegistry())
            ->on(StageSet::define('*', ['fallback' => ['steps' => ['fallback']]]))
            ->on(StageSet::define('updateNewMessage', ['email' => ['steps' => ['email']]]))
            ->compile();

        self::assertSame('*', $registry->match('updateNewMessage')?->name());
    }

    public function test_set_for_prefers_exact_name_over_pattern(): void
    {
        $registry = (new StageRegistry())
            ->on(StageSet::define('start*', ['a' => ['steps' => ['a']]]))
            ->on(StageSet::define('start', ['b' => ['steps' => ['b']]]))
            ->compile();

        self::assertSame('start', $registry->setFor('start')?->name());
        self::assertSame('start*', $registry->setFor('startup')?->name());
    }

    public function test_submit_closure_bound_to_set(): void
    {
        $callable = static function (array $data): bool {
            return true;
        };
        $registry = (new StageRegistry())
            ->on(StageSet::define('updateNewMessage', ['email' => ['steps' => ['email']]]), $callable)
            ->on(StageSet::define('other', ['x' => ['steps' => ['x']]]))
            ->compile();

        self::assertSame($callable, $registry->submitFor('updateNewMessage'));
        self::assertNull($registry->submitFor('other'));

        $submit = $registry->submitFor('updateNewMessage');
        self::assertNotNull($submit);
        self::assertTrue(($submit)(['email' => 'a@b.c']));
    }

    public function test_compile_is_idempotent_and_freezes(): void
    {
        $registry = (new StageRegistry())
            ->on(StageSet::define('a', ['a' => ['steps' => ['a']]]));
        $registry->compile();
        $registry->compile();

        $this->expectException(InvalidArgumentException::class);
        $registry->on(StageSet::define('b', ['b' => ['steps' => ['b']]]));
    }
}