<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppException;
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
 *
 * F16-1 — THE STRING-EQUAL GUARD WAS NOT THE CLASS. The guard above compared
 * the match STRINGS, but HandlerMatcher is first-match-wins over OVERLAPPING
 * GRAMMAR, so three ordinary shapes installed GREEN and never ran: a host's
 * `onMessage()` catch-all `'*'` first (what HandlerRegistry::onMessage()
 * registers), a `'prefix*'` row ahead of the exact match inside it, and a
 * higher-priority superset row from any owner. Each returned 1, sat on the
 * table under `app:{name}`, showed installed in `teleframe:app list` — and
 * never dispatched. refuseShadowed() now decides containment over the whole
 * shipped grammar with string functions alone. The tests below are grouped so
 * the refusal cases and the still-allowed cases sit next to each other: a
 * guard that refuses everything would pass the first group alone.
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

    // ---------------------------------------------------------------------
    // F16-1 — THE OVERLAPPING-PATTERN CLASS. Everything above compares match
    // STRINGS. HandlerMatcher is first-match-wins over OVERLAPPING GRAMMAR, so
    // the string-equal guard missed the common cases: each of these installed
    // GREEN (install() returned 1, the app:{name} tag was on the table,
    // isInstalled() was true, `teleframe:app list` showed the app installed) and
    // the handler NEVER RAN.
    // ---------------------------------------------------------------------

    /**
     * The host's `onMessage()` catch-all — HandlerRegistry::onMessage() registers
     * exactly `'*'` at priority 0 — installed before the app, which is the
     * ORDINARY order when a host subscribes and then declares an app. Before the
     * fix this returned 1 and the app's handler was dead; the matcher resolved
     * the catch-all for the very update the app claimed.
     */
    #[Test]
    public function an_app_trigger_behind_a_host_catch_all_is_refused(): void
    {
        $ran = [];
        $this->table->onMessage(static function (Update $u) use (&$ran): void {
            $ran[] = 'host';
        }, 0);

        try {
            $this->responder->install(AppDefinition::define('app-exact', AppMode::User, triggers: [
                AppTrigger::on('updateNewMessage', static function (Update $u) use (&$ran): void {
                    $ran[] = 'app-exact';
                }),
            ]));
            self::fail('a trigger that can never win the catch-all must be refused, not installed dead');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('app [app-exact] trigger [updateNewMessage] at priority 0 is unreachable', $e->getMessage());
            self::assertStringContainsString('an untagged handler', $e->getMessage());
        }

        self::assertSame(1, $this->table->count(), 'the refused app left no row behind');
        self::assertFalse($this->responder->isInstalled('app-exact'));
    }

    /** The same, driven through the matcher: before the fix the app's row was dead. */
    #[Test]
    public function without_the_guard_a_catch_all_first_kills_the_app_trigger(): void
    {
        $ran = [];
        $this->table->onMessage(static function (Update $u) use (&$ran): void {
            $ran[] = 'host';
        }, 0);
        $this->table->on('updateNewMessage', static function (Update $u) use (&$ran): void {
            $ran[] = 'app-exact';
        }, 0, false, 'app:app-exact');

        $match = (new HandlerMatcher($this->table))->match('updateNewMessage');
        self::assertNotNull($match);
        self::assertSame('*', $match->handler->match, 'the catch-all wins; the app row never runs');

        $invokable = $match->handler->handler;
        if ($invokable instanceof \Closure) {
            $invokable(new Update(['_' => 'updateNewMessage'], 1));
        }
        self::assertSame(['host'], $ran);
    }

    /** A prefix row covers the exact match inside it — same silent wrong, same fix. */
    #[Test]
    public function an_app_trigger_behind_a_prefix_row_is_refused(): void
    {
        $this->table->on('updateNewMessage*', static function (Update $u): void {}, 0, false, 'app:app-prefix');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already registered the match [updateNewMessage*]');

        $this->responder->install(AppDefinition::define('app-exact2', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));
    }

    /**
     * A catch-all at a HIGHER priority covers an app trigger at the default one.
     * Priority is not the escape hatch here: the app's row sorts after, so it
     * loses every update it claims.
     */
    #[Test]
    public function an_app_trigger_behind_a_higher_priority_catch_all_is_refused(): void
    {
        $this->table->on('*', static function (Update $u): void {}, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at priority 0 is unreachable');

        $this->responder->install(AppDefinition::define('app-low', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));
    }

    /**
     * The reciprocal, so the guard is not one-sided: a host's LOWER-priority
     * catch-all does not shadow an app trigger — the app's row sorts first and
     * wins. Priority remains the documented disambiguation lever.
     */
    #[Test]
    public function an_app_trigger_above_a_lower_priority_catch_all_is_allowed_and_wins(): void
    {
        $this->table->onMessage(static function (Update $u): void {}, -10);

        $installed = $this->responder->install(AppDefinition::define('app-above', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        self::assertSame(1, $installed);

        $match = (new HandlerMatcher($this->table))->match('updateNewMessage');
        self::assertNotNull($match);
        self::assertSame('app:app-above', $match->handler->tag, 'the app trigger wins on priority');
    }

    /**
     * A NARROWER row never covers a wider one: `updateNewChannelPost*` does not
     * cover `updateNewMessage`, and refusing that would make two independent apps
     * unusable together.
     */
    #[Test]
    public function a_narrower_prefix_row_does_not_refuse_a_disjoint_trigger(): void
    {
        $this->table->on('updateNewChannelPost*', static function (Update $u): void {}, 0, false, 'app:other');

        $installed = $this->responder->install(AppDefinition::define('app-message', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        self::assertSame(1, $installed);
        self::assertSame(2, $this->table->count());
    }

    /** An EXACT row covers nothing else — 'Foo' must not refuse 'FooBar'. */
    #[Test]
    public function an_exact_row_does_not_cover_a_longer_constructor(): void
    {
        $this->table->on('updateNewMessage', static function (Update $u): void {}, 0, false, 'app:exact-host');

        $installed = $this->responder->install(AppDefinition::define('app-longer', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessageSomethingElse', static function (Update $u): void {}),
        ]));

        self::assertSame(1, $installed);
    }

    /**
     * WITHIN-app overlap is DELIBERATELY still allowed (refuseShadowed's
     * documented decision): one app declaring `'*'` and an exact match is one
     * program, and the table's first-match posture over its own two rows is that
     * author's stated intent. Pinning it here keeps the exemption a decision
     * rather than an accident someone re-reads as an oversight.
     */
    #[Test]
    public function within_app_overlap_is_still_allowed_by_decision(): void
    {
        $installed = $this->responder->install(AppDefinition::define('app-mixed', AppMode::User, triggers: [
            AppTrigger::on('*', static function (Update $u): void {}),
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        self::assertSame(2, $installed);
        self::assertSame(2, $this->table->count());
    }

    /**
     * THE RECIPROCAL ALLOWANCE, pinned as a decision too: a NEW app whose
     * trigger is a superset at a HIGHER priority is allowed, and the app that
     * was already installed stops being reached.
     *
     * This is deliberately NOT a refusal. Priority is the documented
     * disambiguation lever and the shipped contract already says so
     * (a_different_priority_is_allowed_and_the_higher_one_wins). Refusing here
     * would reverse a tested, documented behaviour — and would refuse the host,
     * not the app — to police a shadow the host explicitly asked for. Stated
     * here so nobody reads the absence of a refusal as an oversight.
     */
    #[Test]
    public function a_new_superset_app_above_is_allowed_by_decision_and_takes_the_match(): void
    {
        $this->responder->install(AppDefinition::define('app-narrow', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        $installed = $this->responder->install(AppDefinition::define('app-catchall', AppMode::User, triggers: [
            AppTrigger::on('*', priority: 10, handler: static function (Update $u): void {}),
        ]));

        self::assertSame(1, $installed);
        self::assertSame([], $this->responder->refusals(), 'the host stated the priority — not a refusal');

        $match = (new HandlerMatcher($this->table))->match('updateNewMessage');
        self::assertNotNull($match);
        self::assertSame('app:app-catchall', $match->handler->tag, 'the deliberately-higher app owns the update');
    }

    /**
     * The refusal must be a VALUE a caller that cannot die can report: the
     * record names the app and the trigger, and it is the module's own
     * AppException (what `teleframe:app list` prints).
     */
    #[Test]
    public function a_refusal_is_recorded_for_the_reporting_surface(): void
    {
        $this->table->onMessage(static function (Update $u): void {}, 0);

        try {
            $this->responder->install(AppDefinition::define('app-recorded', AppMode::User, triggers: [
                AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
            ]));
            self::fail('the install must be refused');
        } catch (\InvalidArgumentException) {
            // expected — install() keeps its loud contract
        }

        $refusals = $this->responder->refusals();
        self::assertCount(1, $refusals);
        self::assertInstanceOf(AppException::class, $refusals[0]);
        self::assertStringContainsString('app [app-recorded]', $refusals[0]->getMessage());
        self::assertStringContainsString('updateNewMessage', $refusals[0]->getMessage());

        self::assertNotNull($this->responder->refusalFor('app-recorded'));
        self::assertNull($this->responder->refusalFor('app-installed-clean'),
            'an app that was never refused has no record — and the lookup is by field, not by substring');
    }

    /** A clean install records nothing. */
    #[Test]
    public function a_clean_install_records_no_refusal(): void
    {
        $this->responder->install(AppDefinition::define('app-clean', AppMode::User, triggers: [
            AppTrigger::on('updateNewMessage', static function (Update $u): void {}),
        ]));

        self::assertSame([], $this->responder->refusals());
        self::assertNull($this->responder->refusalFor('app-clean'));
    }

    /** @return list<string|null> */
    private function tags(): array
    {
        return array_map(static fn ($handler): ?string => $handler->tag, $this->table->all());
    }
}
