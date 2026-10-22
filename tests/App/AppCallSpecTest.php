<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use PHPUnit\Framework\TestCase;

/**
 * AppCallSpec (design §2/G4, §5) — the declarative-app → SchedulePublisher
 * payload composer: one `app-call-{app}-{call}-{digest}` entry per AppCall,
 * carrying interval_seconds, the activity string the schedule host fires
 * (`telegram.app.run-call {app} {call}`), and the app/call names the payload
 * route is keyed on. callId() is the wire key form.
 */
final class AppCallSpecTest extends TestCase
{
    private AppCallSpec $spec;

    protected function setUp(): void
    {
        $this->spec = new AppCallSpec;
    }

    private static function handler(): \Closure
    {
        return static function (Update $u): void {};
    }

    private function app(array $calls = []): AppDefinition
    {
        return AppDefinition::define('echo-bot', AppMode::User, [
            AppTrigger::on('updateNewMessage', self::handler()),
        ], $calls);
    }

    public function test_install_spec_builds_one_app_call_payload_entry_per_call(): void
    {
        $spec = $this->spec->installSpec($this->app([
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
            AppCall::every('health', 60, 'messages.sendMessage', ['message' => 'ok']),
        ]));

        $ids = array_keys($spec);

        self::assertCount(2, $ids);
        self::assertStringStartsWith('app-call-echo-bot-status-ping-', $ids[0]);
        self::assertStringStartsWith('app-call-echo-bot-health-', $ids[1]);
        self::assertSame([
            'interval_seconds' => 3600,
            'activity' => 'telegram.app.run-call echo-bot status-ping',
            'app' => 'echo-bot',
            'call' => 'status-ping',
        ], $spec[$ids[0]]);
        self::assertSame([
            'interval_seconds' => 60,
            'activity' => 'telegram.app.run-call echo-bot health',
            'app' => 'echo-bot',
            'call' => 'health',
        ], $spec[$ids[1]]);
    }

    public function test_call_id_is_the_app_call_wire_key(): void
    {
        $app = $this->app([AppCall::every('status-ping', 3600, 'messages.sendMessage')]);
        $call = $app->calls()[0];

        $id = $this->spec->callId($app, $call);

        self::assertSame($id, array_key_first($this->spec->installSpec($app)),
            'callId() is the key installSpec() writes under');
        self::assertStringStartsWith('app-call-echo-bot-status-ping-', $id);
    }

    public function test_call_id_is_deterministic_so_a_redeploy_refreshes_one_schedule(): void
    {
        // Idempotence is the whole reason the SDK client describes before it
        // creates. A non-deterministic id would stack a second schedule on every
        // deploy instead of refreshing the one that is there.
        $app = $this->app([AppCall::every('status-ping', 3600, 'messages.sendMessage')]);

        self::assertSame(
            $this->spec->callId($app, $app->calls()[0]),
            $this->spec->callId($app, $app->calls()[0]),
            'the same (app, call) must always produce the same schedule id',
        );
    }

    public function test_two_apps_declaring_the_same_call_name_get_distinct_schedule_ids(): void
    {
        // THE REGRESSION. The id used to be `app-call-{callName}`, unique only
        // WITHIN one app, so these two apps installed the SAME schedule id. That
        // was harmless only while the temporal driver could not arm anything;
        // now that a real SDK-backed createOrUpdateSchedule() is reachable, app
        // B's install() found app A's schedule and updated it IN PLACE — app A's
        // cadence and activity silently replaced, no error anywhere.
        $alpha = AppDefinition::define('alpha-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alpha']),
        ]);
        $beta = AppDefinition::define('beta-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage', ['message' => 'beta']),
        ]);

        $alphaCall = $alpha->calls()[0];
        $betaCall = $beta->calls()[0];

        self::assertNotSame(
            $this->spec->callId($alpha, $alphaCall),
            $this->spec->callId($beta, $betaCall),
            'two apps sharing a call name must not share a schedule id',
        );

        // And end to end: neither app's payload is lost or folded into the
        // other's. This is the half the id collision hid — AppRuntime merges
        // per-app payloads with `+=`, so a shared key dropped the second app's
        // call before the publisher ever saw it.
        $merged = $this->spec->installSpec($alpha) + $this->spec->installSpec($beta);

        self::assertCount(2, $merged, 'neither app call may be dropped from the merged payload');

        $byApp = [];
        foreach ($merged as $payload) {
            $byApp[$payload['app']] = $payload;
        }

        self::assertSame('alpha-bot', $byApp['alpha-bot']['app'], "alpha-bot's row still names itself");
        self::assertSame(3600, $byApp['alpha-bot']['interval_seconds'], "app alpha's cadence must survive");
        self::assertSame(
            'telegram.app.run-call alpha-bot status-ping',
            $byApp['alpha-bot']['activity'],
            'alpha-bot keeps its OWN activity type — a shared key folded beta into it',
        );
        self::assertSame('beta-bot', $byApp['beta-bot']['app'], "beta-bot's row still names itself");
        self::assertSame(60, $byApp['beta-bot']['interval_seconds'], "app beta's cadence must survive");
        self::assertSame(
            'telegram.app.run-call beta-bot status-ping',
            $byApp['beta-bot']['activity'],
            'beta-bot keeps its OWN activity type — both apps declare status-ping and both survive',
        );
    }

    public function test_two_app_and_call_pairs_that_read_the_same_joined_string_still_differ(): void
    {
        // A separator-joined id alone would NOT be injective: these two pairs
        // both render as `app-call-a-b-c`. The digest over `app . NUL . call` is
        // what keeps the mapping one-to-one, and it is the reason the id is not
        // simply `app-call-{app}-{call}`.
        $left = AppCall::every('c', 60, 'sendMessage');
        $right = AppCall::every('b-c', 60, 'sendMessage');

        $leftId = $this->spec->callId(
            AppDefinition::define('a-b', AppMode::Bot, calls: [$left]),
            $left,
        );
        $rightId = $this->spec->callId(
            AppDefinition::define('a', AppMode::Bot, calls: [$right]),
            $right,
        );

        self::assertStringStartsWith('app-call-a-b-c-', $leftId);
        self::assertStringStartsWith('app-call-a-b-c-', $rightId);
        self::assertNotSame($leftId, $rightId);
    }

    public function test_install_spec_with_no_calls_yields_an_empty_payload(): void
    {
        self::assertSame([], $this->spec->installSpec($this->app()));
    }

    /**
     * The (app, call) name shapes a host actually writes, each of which declare()
     * accepts and each of which the wire type must therefore be able to CARRY.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function representablePairs(): array
    {
        return [
            ['echo-bot', 'status-ping'],
            ['a-b', 'c'],
            ['a', 'b-c'],
            ['a.b', 'c_d'],
            ['UPPER.Case-App', 'MiXeD_case.call'],
            ['9lives', '0'],
            ['x', 'y'],
            ['echo_bot_2', 'status.ping-2'],
            ['échó-bot', 'státus-ping'],
        ];
    }

    public function test_worker_and_catalog_agree_for_every_name_define_accepts(): void
    {
        // THE F16-4 CONTRACT, stated as a property over every accepted name rather
        // than as one probe: what a schedule arms, what the worker registers, and
        // what the catalog can parse back must be the SAME string.
        //
        // Before the declare-time guard, a name holding the separator broke this in
        // a way nothing reported: the schedule armed, the worker registered the very
        // same type (so the tick ran — the activity reads app/call off the payload),
        // and the catalog printed "NOT SERVED by any worker in this package … every
        // tick will fail to start a workflow" about a type this package HAD
        // registered. The false red is exactly a disagreement between these two.
        $catalog = new WorkflowTypeCatalog;

        foreach (self::representablePairs() as [$app, $call]) {
            $payload = $this->spec->installSpec(
                AppDefinition::define($app, AppMode::Bot, calls: [
                    AppCall::every($call, 3600, 'messages.sendMessage', ['message' => 'alive']),
                ]),
            );

            self::assertCount(1, $payload, sprintf('one call arms one entry for (%s, %s)', $app, $call));

            $type = $payload[array_key_first($payload)]['activity'];

            self::assertSame(
                WorkflowTypeCatalog::appCallType($app, $call),
                $type,
                sprintf('the producer and the catalog must build the same type for (%s, %s)', $app, $call),
            );
            self::assertSame(
                [$app, $call],
                $catalog->parseAppCall($type),
                sprintf('the catalog must recover the declared pair (%s, %s) from the type it was built from', $app, $call),
            );
            self::assertTrue(
                $catalog->serves($type),
                sprintf('the catalog must not print a false red about the type of (%s, %s): %s', $app, $call, $catalog->verdict($type)),
            );

            $worker = new TemporalWorker(appCalls: [[$app, $call]]);

            self::assertSame(
                [$type],
                $worker->appCallTypes(),
                sprintf('a worker started for (%s, %s) registers exactly the type the schedule armed', $app, $call),
            );
            self::assertContains(
                $type,
                $worker->servedTypes(),
                sprintf('and it is a served type of that worker (%s, %s)', $app, $call),
            );
        }
    }

    public function test_two_accepted_pairs_that_read_the_same_dashed_string_still_render_different_types(): void
    {
        // WHY THE SCHEDULE ID NEEDS A DIGEST BUT THE TYPE STRING CANNOT HAVE ONE.
        //
        // callId() joins the pair with '-', so ('a-b','c') and ('a','b-c') both read
        // `app-call-a-b-c` and the digest over `app . NUL . call` is what keeps them
        // apart. The workflow type joins the pair with a SPACE, so those two pairs
        // render `telegram.app.run-call a-b c` and `telegram.app.run-call a b-c` —
        // different strings, and no digest is needed.
        //
        // That is only true while a declared name may not contain a space. One spaced
        // name and the mapping stops being injective: ('my app','status-ping') and
        // ('my','app status-ping') both render `telegram.app.run-call my app
        // status-ping`, so a worker started for one pair serves the other pair's
        // type and the pair is unrecoverable from the string. This assertion pins the
        // property the declare-time guard in AppDefinition::define() buys.
        $left = $this->spec->installSpec(AppDefinition::define('a-b', AppMode::Bot, calls: [
            AppCall::every('c', 60, 'messages.sendMessage'),
        ]));
        $right = $this->spec->installSpec(AppDefinition::define('a', AppMode::Bot, calls: [
            AppCall::every('b-c', 60, 'messages.sendMessage'),
        ]));

        $leftType = $left[array_key_first($left)]['activity'];
        $rightType = $right[array_key_first($right)]['activity'];

        self::assertSame('telegram.app.run-call a-b c', $leftType);
        self::assertSame('telegram.app.run-call a b-c', $rightType);
        self::assertNotSame($leftType, $rightType, 'the type string must not collide the way a dashed id does');

        $catalog = new WorkflowTypeCatalog;

        self::assertSame(['a-b', 'c'], $catalog->parseAppCall($leftType));
        self::assertSame(['a', 'b-c'], $catalog->parseAppCall($rightType));
    }

    public function test_a_name_holding_the_wire_separator_is_refused_so_no_type_string_is_built(): void
    {
        // The producer half of F16-4, pinned next to the composer: this is the exact
        // input that used to arm `telegram.app.run-call my app status-ping`, which no
        // parser could take apart again. The refusal is asserted (not the absence of
        // a failure): define() is the only door to an AppDefinition, so a refused name
        // cannot reach installSpec() at all.
        // @see AppNameRepresentsOnTheWireTest for the guard's full contract
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may not contain a space');

        AppDefinition::define('my app', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
        ]);
    }
}
