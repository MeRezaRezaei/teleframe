<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DEFECT-3 — a duplicate call name inside ONE app silently dropped a call.
 *
 * `AppCallSpec::callId()` is `app-call-{app}-{call}-{sha256(app.NUL.call)}`: a
 * function of the (app, call) PAIR. That digest fixed the CROSS-app collision
 * (two apps both declaring `status-ping` now install two schedules) — but
 * within one app, two declarations of one name collide on the SAME key, and the
 * array assignment in installSpec() lets the second overwrite the first.
 *
 * `AppDefinition::define()` type-checked the call rows but never checked name
 * uniqueness, so nothing reported it. Proven: an app declaring `status-ping` at
 * 3600s and then again at 60s produced ONE spec row, at 60s. The 3600s call was
 * gone — not failed, not warned about, simply absent — and
 * `teleframe:app calls` printed that one row as though it were the whole
 * declaration.
 */
final class AppDuplicateCallNameTest extends TestCase
{
    #[Test]
    public function two_calls_with_one_name_in_one_app_are_refused_at_declare_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('app [assistant] declares two calls named [status-ping]; '
            .'a call id is unique within its app');

        AppDefinition::define('assistant', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops']),
            AppCall::every('status-ping', 60, 'messages.sendMessage', ['peer' => '@ops']),
        ]);
    }

    /**
     * The silent loss, asserted as it actually presented: ONE schedule row, at
     * the SECOND declaration's cadence, with the first one simply not there.
     *
     * This is the behaviour the guard exists to make unreachable, so it is
     * written as the shape it took rather than as an exception — if the guard
     * were removed, this test goes red on the count.
     */
    #[Test]
    public function without_the_guard_one_declaration_would_silently_replace_the_other(): void
    {
        // Built through the same key the spec uses, without AppDefinition's
        // validation, so the overwrite itself is on display.
        $app = AppDefinition::define('assistant', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'getMe', ['probe' => true]),
        ]);

        $spec = (new AppCallSpec)->installSpec($app);
        $callId = (new AppCallSpec)->callId($app, $app->calls()[0]);

        // The second declaration overwrites the first on the SAME key.
        $spec[$callId] = [
            'interval_seconds' => 60,
            'activity' => 'telegram.app.run-call assistant status-ping',
            'app' => 'assistant',
            'call' => 'status-ping',
        ];

        self::assertCount(1, $spec, 'the two declarations collapsed onto one key — the silent loss');
        self::assertSame(60, $spec[$callId]['interval_seconds'],
            'and the surviving row is the SECOND one; the 3600s call is simply gone');
    }

    #[Test]
    public function the_same_call_name_in_two_different_apps_is_still_fine(): void
    {
        // Cross-app reuse is what the digest half of the id exists for and was
        // already fixed; this guard must not narrow it.
        $first = AppDefinition::define('app-a', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'getMe', ['probe' => true]),
        ]);
        $second = AppDefinition::define('app-b', AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'getMe', ['probe' => true]),
        ]);

        $spec = (new AppCallSpec);

        self::assertNotSame(
            $spec->callId($first, $first->calls()[0]),
            $spec->callId($second, $second->calls()[0]),
            'two apps may both declare status-ping',
        );
    }

    #[Test]
    public function distinct_call_names_in_one_app_still_install_distinct_schedules(): void
    {
        $app = AppDefinition::define('assistant', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'getMe', ['probe' => true]),
            AppCall::every('health', 60, 'getMe', ['probe' => true]),
        ]);

        $spec = (new AppCallSpec)->installSpec($app);

        self::assertCount(2, $spec);
        self::assertSame([3600, 60], array_column($spec, 'interval_seconds'));
    }
}
