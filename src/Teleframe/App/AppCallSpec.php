<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

/**
 * AppCallSpec (design §2/G4, §5) — builds the SchedulePublisher->install()
 * payload from a declarative app's scheduled calls: one
 * `app-call-{app}-{call}-{digest}` entry per AppCall, carrying the interval,
 * the activity string the schedule host fires (`telegram.app.run-call
 * {app} {call}` → AppRuntime::runCall) and the app/call names the payload
 * route is keyed on. Mirrors BackupScheduleSpec::installSpec() exactly in
 * posture and return contract; the payload gains `app` and `call` keys on
 * top of the shared `interval_seconds`/`activity` shape so a schedule host
 * can re-dispatch without parsing the activity string.
 *
 * Posture (design §5): schedules ride the existing SchedulePublisher seam
 * — the NullSchedulePublisher default records `skipped` per schedule, and
 * installSpec() is safe to hand to it unconfigured. Config, not
 * construction, decides whether schedules run.
 *
 * `interval_seconds` IS the cadence, and the recurring half of the schedule
 * seam (Store\Schedules\RecurringScheduleClient) reads it: bind that and each
 * call recurs on its own interval. Bind only the one-shot
 * TemporalScheduleClient and the publisher says so in its status rather than
 * implying a cadence it cannot honour. Under the 'null' default neither key is
 * touched.
 *
 * Pure data, no container, plain-PHP constructible (standalone-smoke).
 * Zero regex.
 */
final class AppCallSpec
{
    /**
     * Hex characters of the sha256 digest appended to every schedule id. Long
     * enough that a collision between two declared (app, call) pairs is not a
     * thing a host can hit in practice, short enough to stay readable in a
     * terminal.
     */
    private const DIGEST_LENGTH = 12;

    /**
     * The SchedulePublisher->install() payload for one app:
     * {app-call-{app}-{call}-{digest} => {interval_seconds, activity, app, call}}.
     *
     * @return array<string, array{interval_seconds: int, activity: string, app: string, call: string}>
     */
    public function installSpec(AppDefinition $app): array
    {
        $spec = [];

        foreach ($app->calls() as $call) {
            $spec[self::callId($app, $call)] = [
                'interval_seconds' => $call->intervalSeconds,
                'activity' => 'telegram.app.run-call '.$app->name.' '.$call->name,
                'app' => $app->name,
                'call' => $call->name,
            ];
        }

        return $spec;
    }

    /**
     * The wire key one scheduled call installs under (design §5):
     * `app-call-{app}-{call}-{digest}` — the id a schedule host (and
     * AppCommand calls) addresses for this app's call.
     *
     * WHY THE APP NAME IS IN THE ID (the collision this fixes). The id used to
     * be `app-call-{callName}` alone, which is unique only WITHIN one app. Two
     * apps that each declare `status-ping` therefore installed the SAME id,
     * and that was harmless for exactly as long as the `temporal` driver could
     * not do anything (it could only throw). The package now supplies a real
     * SDK-backed RecurringScheduleClient whose createOrUpdateSchedule() is a
     * describe-then-upsert, so the second app's install() FOUND the first
     * app's schedule and `update()`d it in place: app A's cadence and activity
     * silently replaced, no error anywhere. Worse, AppRuntime::calls() and
     * AppRuntime::installCalls() merge per-app payloads with `+=`, so the
     * second app's call was dropped from the payload BEFORE the publisher saw
     * it. Both halves of "silently lose app B's call" are closed by making the
     * id a function of the (app, call) PAIR.
     *
     * WHY THE DIGEST. A separator-joined id is only injective if the app and
     * call names cannot contain the separator: `app="a-b", call="c"` and
     * `app="a", call="b-c"` would both read `app-call-a-b-c`. Reintroducing a
     * silent-overwrite collision — the very failure this class exists to
     * prevent — one separator deeper is not a trade worth making.
     *
     * Declared names cannot reach that state: AppDefinition::define() refuses a
     * blank name AND a name carrying the wire separator
     * (self::refuseUnrepresentableName()), so for anything that came through
     * declare() the plain joined id is already injective. The digest is not
     * redundant belt-and-braces, though, because AppCallSpec is constructible
     * directly — an id assembled from an arbitrary pair must not be one
     * declare() would have refused. It is taken over `app . NUL . call`, which
     * no declared name can contain, so the mapping is injective up to the
     * digest's own collision resistance. The readable app/call prefix is kept
     * in front of it: ids surface in CLI output and in `teleframe:app calls`,
     * and a schedule id an operator cannot read is a bad trade in the other
     * direction. Routing never depends on the prefix — the payload carries
     * `app` and `call` as their own keys.
     *
     * WHY IT IS SAFE TO CHANGE THE FORM. The id is not a compatibility
     * surface: until the SDK-backed client landed, driver `temporal` could not
     * arm a schedule at all, so no deployed server holds an `app-call-*` id
     * created by this package. There is no consumer, no migration, and no
     * cross-version wire contract to keep. (The id is also NOT covered by
     * AGENTS.md's opaque-key rule: that rule protects a CONSUMER's existing
     * Redis keys across a package upgrade — `tg:stream:updates` and friends —
     * and there is no such consumer here, because these ids could never be
     * created before this slice.)
     *
     * DETERMINISM IS LOAD-BEARING. The digest is a pure function of the two
     * names, so re-running install() on the next deploy produces the SAME id
     * and the SDK client refreshes one schedule in place rather than stacking
     * a second — the idempotence SdkTemporalScheduleClient is built for. A
     * random or time-based suffix would have broken it.
     */
    public function callId(AppDefinition $app, AppCall $call): string
    {
        return sprintf(
            'app-call-%s-%s-%s',
            $app->name,
            $call->name,
            substr(hash('sha256', $app->name."\0".$call->name), 0, self::DIGEST_LENGTH),
        );
    }
}
