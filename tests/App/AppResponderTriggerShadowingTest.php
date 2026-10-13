<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppResponder;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DEFECT-4 — a registered cross-app handler could be UNREACHABLE, silently.
 *
 * `HandlerMatcher::match()` is first-match-wins over `HandlerRegistry::all()`,
 * which sorts priority DESC with a STABLE tie-break — so with two apps
 * declaring the same match at the same priority, the FIRST registered runs and
 * the second is dead.
 *
 * `AppResponder::install()` had no cross-app check. Proven: apps `app-a` and
 * `app-b` both declaring `on('updateNewMessage')` at default priority 0 both
 * installed — registry count 2, both `app:app-a` and `app:app-b` tags present —
 * and the matcher resolved `app:app-a`. `app-b`'s closure was never invoked.
 * No error, no warning, and `teleframe:app` listed both apps as installed.
 * Raising `app-b` to priority 10 flipped the winner, which is the same silent
 * shadow seen from the other side.
 *
 * A silently dead handler is the exact failure class this repo keeps paying to
 * remove, so `install()` now REFUSES before registering anything.
 */
final class AppResponderTriggerShadowingTest extends TestCase
{
    private HandlerRegistry $table;

    private AppResponder $responder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = new HandlerRegistry;
        $this->responder = new AppResponder($this->table);
    }

    #[Test]
    public function a_second_app_may_not_register_the_same_match_at_the_same_priority(): void
    {
        $first = AppDefinition::define('app-a', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]);

        self::assertSame(1, $this->responder->install($first));

        $second = AppDefinition::define('app-b', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('app [app-b] trigger [updateNewMessage] at priority 0 is unreachable');

        $this->responder->install($second);
    }

    /**
     * The refusal must leave the table exactly as it was — no half-installed
     * app, which would be a worse state than either outcome.
     */
    #[Test]
    public function a_refused_app_registers_nothing_at_all(): void
    {
        $this->responder->install(AppDefinition::define('app-a', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        // Two triggers: the first conflicts, the second would be harmless. A
        // check that ran per-trigger as it installed would have leaked the
        // second one into the table.
        $conflicting = AppDefinition::define('app-b', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
            AppTrigger::on('updateNewChannelPost', static function (Update $u): void {}),
        ]);

        try {
            $this->responder->install($conflicting);
            self::fail('the shadowing install must be refused');
        } catch (\InvalidArgumentException) {
            // expected
        }

        self::assertSame(1, $this->table->count(), 'no row from the refused app may exist');
        self::assertSame(['app:app-a'], $this->tags(), 'the winner is untouched');
        self::assertFalse($this->responder->isInstalled('app-b'));
    }

    /** The bug itself, shown rather than described. */
    #[Test]
    public function without_the_guard_the_second_app_would_be_dead(): void
    {
        // Bypasses AppResponder's check by driving the table directly — this is
        // the state the matcher actually saw.
        $this->table->on('updateNewMessage', static fn (Update $u) => 'app-a', 0, false, 'app:app-a');
        $this->table->on('updateNewMessage', static fn (Update $u) => 'app-b', 0, false, 'app:app-b');

        $match = (new HandlerMatcher($this->table))->match('updateNewMessage');

        self::assertNotNull($match);
        self::assertSame('app:app-a', $match->handler->tag,
            'first-match-wins picks app-a; app-b is registered and unreachable');

        // Both tags are present, so nothing else in the system can tell.
        self::assertSame(['app:app-a', 'app:app-b'], $this->tags());
    }

    /**
     * A different priority is the documented way to disambiguate, so it must
     * NOT be refused — the host has stated which one should win.
     */
    #[Test]
    public function a_different_priority_is_allowed_and_the_higher_one_wins(): void
    {
        $this->responder->install(AppDefinition::define('app-a', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        $installed = $this->responder->install(AppDefinition::define('app-b', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', priority: 10, handler: static function (Update $u): void {}),
        ]));

        self::assertSame(1, $installed);

        $match = (new HandlerMatcher($this->table))->match('updateNewMessage');
        self::assertNotNull($match);
        self::assertSame('app:app-b', $match->handler->tag,
            'the deliberately-higher-priority app wins, which is the host\'s stated intent');
    }

    /** A different match on the same update type is not a conflict. */
    #[Test]
    public function different_matches_never_conflict(): void
    {
        $this->responder->install(AppDefinition::define('app-a', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        $installed = $this->responder->install(AppDefinition::define('app-b', AppMode::User, triggers: [
            AppTrigger::on('updateNewChannelPost', static function (Update $u): void {}),
        ]));

        self::assertSame(1, $installed);
        self::assertSame(2, $this->table->count());
    }

    /** The idempotence the table already had must survive the new check. */
    #[Test]
    public function reinstalling_the_same_app_is_still_a_no_op(): void
    {
        $app = AppDefinition::define('app-a', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]);

        self::assertSame(1, $this->responder->install($app));
        self::assertSame(0, $this->responder->install($app), 'a same-app reinstall is not a shadow');
        self::assertSame(1, $this->table->count());
    }

    /** A host handler already on the table shadows an app trigger just as hard. */
    #[Test]
    public function an_untagged_host_handler_on_the_same_match_is_refused_against(): void
    {
        $this->table->on('updateNewMessage', static fn (Update $u) => 'host', 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('an untagged handler');

        $this->responder->install(AppDefinition::define('app-a', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));
    }

    /** @return list<string|null> */
    private function tags(): array
    {
        return array_map(static fn ($handler): ?string => $handler->tag, $this->table->all());
    }
}
