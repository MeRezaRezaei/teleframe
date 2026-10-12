<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\Update;
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

        self::assertSame('alpha-bot', $byApp['alpha-bot']['app']);
        self::assertSame(3600, $byApp['alpha-bot']['interval_seconds'], "app alpha's cadence must survive");
        self::assertSame('telegram.app.run-call alpha-bot status-ping', $byApp['alpha-bot']['activity']);
        self::assertSame('beta-bot', $byApp['beta-bot']['app']);
        self::assertSame(60, $byApp['beta-bot']['interval_seconds'], "app beta's cadence must survive");
        self::assertSame('telegram.app.run-call beta-bot status-ping', $byApp['beta-bot']['activity']);
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
}
