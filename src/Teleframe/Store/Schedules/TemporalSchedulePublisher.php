<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use RuntimeException;

/**
 * Temporal-backed SchedulePublisher (Task 3, Cycle 19).
 *
 * Implements the user's stated preference verbatim (2026-09-16): "i prefer
 * rabit mq to be used as queue and temporral for shcduls". RabbitMQ is the
 * queue; this is the schedule side.
 *
 * TWO TRUTHS THIS CLASS USED TO GET WRONG (Cycle 19 audit, both goals marked
 * `[x]` on claims that could not happen):
 *
 *  1. "Client injected and lazy so the smoke gate still constructs it with no
 *     container" was TRUE of the class and FALSE of every production wiring:
 *     both providers built `new TemporalSchedulePublisher(dsn:…, taskQueue:…)`
 *     with NO client, and the publisher never builds one either. The only
 *     implementor of TemporalScheduleClient in the whole tree was an anonymous
 *     class in a test. So driver 'temporal' could ONLY ever reach the
 *     no-client throw — the recurring dump never fired itself. The providers
 *     now refuse at RESOLVE (config) time with a message naming exactly what
 *     the host must bind, instead of throwing deep inside install().
 *
 *  2. The payload contracts disagreed. Producers (BackupScheduleSpec,
 *     AppCallSpec) emit {interval_seconds, activity, …}; this publisher read
 *     `workflow`/`args`/`task_queue` and called startWorkflow ONCE per entry —
 *     so `interval_seconds` was silently DISCARDED and a "recurring schedule"
 *     was a single run. It is now read: given a RecurringScheduleClient the
 *     interval arms a real recurring schedule; given only the one-shot
 *     TemporalScheduleClient the status string SAYS the run is not recurring.
 *
 * The client is INJECTED and nothing dials at construction — a publisher that
 * dialled Temporal in its constructor would break the standalone-smoke gate and
 * would open a queue connection during a Laravel boot.
 *
 * Plain-PHP constructible, zero container, zero regex.
 */
final class TemporalSchedulePublisher implements SchedulePublisher
{
    /**
     * schedule id => the status install() reported for it. This publisher's own
     * record of what IT armed, in THIS process. It is what describe() reports,
     * so describe() cannot claim a schedule is armed before install() has run
     * one. Not a mirror of the server: nothing here dials to read state back.
     *
     * @var array<string, string>
     */
    private array $armed = [];

    /**
     * The workflow TYPES this process armed, deduplicated.
     *
     * Recorded in install() alongside the status strings, because the status
     * string alone is prose: describe() has to be able to answer "does a worker
     * in this package serve what I just armed?" per type, and that answer is
     * only meaningful for types this process actually armed. @see self::workerCoverage()
     *
     * @var list<string>
     */
    private array $armedTypes = [];

    /**
     * The one registry of served workflow types. NOT a constructor parameter:
     * the providers that build this publisher (and the third-party hosts that
     * do the same) pass dsn/client/taskQueue and nothing else, and every one of
     * those call sites is owned by another slice. Defaulting here keeps the
     * signature untouched while still routing the disclosure text through the
     * same catalog TemporalWorker registers from.
     */
    private readonly WorkflowTypeCatalog $types;

    /**
     * The ONE task queue every arm site falls back to.
     *
     * The queue used to be configured in two places and hardcoded in two, and
     * nothing reconciled them: `app` and `backup` read a config key, `tstore`
     * and `notify` passed the literal 'default'. One worker then served at most
     * half of what the host believed it had armed, and the failure was total
     * silence — schedules report `armed ... recurring every ...`, the worker
     * reports `polling task queue 'default'`, and neither line is compared
     * against the other. bin/teleframe-worker resolves the same variable on the
     * serve side, so one setting moves both.
     *
     * Deliberately NOT a config() read: this class is plain-PHP constructible
     * with no container, which is what keeps the standalone-smoke gate honest.
     */
    public static function defaultTaskQueue(): string
    {
        $env = getenv('TELEFRAME_TEMPORAL_TASK_QUEUE');

        if ($env === false) {
            return 'default';
        }

        $trimmed = trim($env);

        return $trimmed === '' ? 'default' : $trimmed;
    }

    public function __construct(
        private readonly string $dsn,
        private readonly ?TemporalScheduleClient $client = null,
        private readonly string $taskQueue = 'default',
    ) {
        $this->types = new WorkflowTypeCatalog;
    }

    public function dsn(): string
    {
        return $this->dsn;
    }

    /** True when this publisher can actually arm a RECURRING schedule. */
    public function armsRecurring(): bool
    {
        return $this->client instanceof RecurringScheduleClient;
    }

    /**
     * Arm one schedule per configured spec entry, recurring when the spec
     * carries a cadence and the client can express one.
     *
     * @param  array<string, array<string, mixed>>  $schedules
     * @return array<string, string>
     */
    public function install(array $schedules): array
    {
        if ($this->client === null) {
            throw new RuntimeException(self::missingClientMessage(count($schedules)));
        }

        $status = [];
        $types = [];

        foreach ($schedules as $id => $spec) {
            $id = (string) $id;
            $interval = $this->intervalOf($spec);
            $queue = (string) ($spec['task_queue'] ?? $this->taskQueue);

            // THE PRODUCER→CONSUMER SEAM (the defect this closes). Every producer
            // keys the WORK on its spec entry: AppCallSpec emits `app`/`call`,
            // which is the only thing AppRunCallWorkflow::run() reads to route the
            // tick to a call. This publisher read `args` — a key NO producer emits
            // — so every app-call schedule was armed with an EMPTY payload and
            // RunAppCallActivity::runCall() refused by name on EVERY tick, while
            // the status line read `armed telegram.app.run-call … recurring every
            // 3600s; worker: served by this package`. It survived because no test
            // crossed installSpec → publisher → encoded input → the workflow's own
            // `$payload['app']` read; the tick test hand-supplied ['echo','health',[]].
            //
            // So the workflow input is built FROM THE SPEC ENTRY, not from an
            // optional key nobody sets: every producer-keyed value travels with the
            // run, and only the control keys the publisher itself consumes
            // (interval_seconds, activity/workflow, task_queue, args) are held back.
            // A producer that keys on anything else is routed for free.
            $args = $this->payloadOf($spec);

            // Producers key the work on `activity` (a command/activity string
            // like 'teleframe:tstore backup'); the older surface spelled it
            // `workflow`. Accept both, prefer what the producers emit.
            $activity = (string) ($spec['activity'] ?? $spec['workflow'] ?? $id);

            // The workflow type the server will dispatch on — the very string
            // createOrUpdateSchedule()/startWorkflow() forwards. Recorded so
            // describe() can state per type whether a worker in this package
            // serves it. @see self::workerCoverage()
            $types[$activity] = true;

            if ($this->client instanceof RecurringScheduleClient && $interval !== null) {
                $scheduleId = $this->client->createOrUpdateSchedule(
                    $id,
                    $activity,
                    $interval,
                    $queue,
                    $args,
                );

                $status[$id] = sprintf(
                    'armed %s recurring every %ds (schedule %s, %s@%s); %s',
                    $activity,
                    $interval,
                    $scheduleId,
                    $queue,
                    $this->dsn,
                    $this->disclosure($activity),
                );

                continue;
            }

            // One-shot fallback. Named honestly: a started workflow runs once
            // and never again. A stated interval_seconds is passed along in the
            // payload rather than dropped; an ABSENT one is not fabricated as 0
            // (a zero cadence is not "no cadence" - it would mean fire forever).
            if ($interval !== null) {
                $args['interval_seconds'] = $interval;
            }

            $workflowId = $this->client->startWorkflow($activity, $queue, $args);

            $status[$id] = $interval === null
                ? sprintf('started ONCE, not recurring (no interval_seconds in spec) %s (%s@%s); %s', $activity, $workflowId, $this->dsn, $this->disclosure($activity))
                : sprintf('started ONCE, not recurring — client cannot arm a cadence; interval_seconds=%d was passed in the payload (%s@%s); %s', $interval, $activity, $this->dsn, $this->disclosure($activity));
        }

        // Recorded only once every entry has succeeded: install() throws before
        // the loop body on a missing client, and a throw from the client aborts
        // it, so reaching this line means the whole payload was handled.
        $this->armed = $status;
        $this->armedTypes = array_keys($types);

        return $status;
    }

    public function describe(): string
    {
        $line = "Schedule driver: temporal. Temporal DSN: {$this->dsn}, task queue: {$this->taskQueue}.\n";

        if ($this->client === null) {
            return $line.'No Temporal client is bound — install() will refuse until one is.';
        }

        if (! $this->client instanceof RecurringScheduleClient) {
            return $line.'WARNING: the bound client is one-shot — schedules run ONCE per install, '
                .'not on a cadence. Bind a '.RecurringScheduleClient::class.' to arm real recurring schedules.'
                .self::armedStatus();
        }

        return $line.'Recurring schedules are armed through '.RecurringScheduleClient::class.'.'
            .self::armedStatus()
            .$this->workerFooter();
    }

    /**
     * The standing worker sentence, printed whether or not anything is armed.
     *
     * The per-type half (workerCoverage) can only speak about types THIS
     * process armed — describe() dials nothing and cannot enumerate a server.
     * This footer covers the other half of the same truth: the driver is only
     * the schedule side. A schedule whose workflow type no worker implements
     * ticks into nothing, and a reader of this text must be told that the
     * package ships the worker and what starting it looks like, or the absence
     * of that fact is itself the false green.
     */
    private function workerFooter(): string
    {
        return "\nWorker: the driver is only the SCHEDULE side. This package ships a worker too ("
            .count(WorkflowTypeCatalog::fixedTypes()).' fixed workflow types plus one per declared app call); '
            .'run `'.Worker\TemporalWorker::ENTRY_POINT.' --describe` to list them, and start it on task queue '
            .'`'.$this->taskQueue.'`. A workflow type no worker implements fails on EVERY tick — arm one only if '
            .'a worker you run yourself serves it, and read the per-type verdict this publisher prints for each '
            .'schedule it arms. No live Temporal server has been exercised by this package, so arming is not '
            .'proof of firing.';
    }

    /**
     * How many schedules THIS publisher instance has actually armed.
     *
     * describe() used to open with the unconditional "tstore maintenance
     * schedules are enabled", which was wrong twice over: it claimed a state
     * that no code had established (nothing is armed until install() runs, and
     * the string was printed by CLI paths that only ever describe), and "tstore"
     * is simply the wrong module on the app seam and the backup seam, which bind
     * this same class.
     *
     * The honest scope is PER PROCESS. This class holds no server connection and
     * dials nothing from describe(), so it cannot know what a Temporal server
     * currently holds; saying so in the text is better than either claiming
     * "enabled" or implying a live query.
     *
     * @return string the per-process armed-status sentence
     */
    private function armedStatus(): string
    {
        if ($this->armed === []) {
            return "\nNothing is armed by this publisher yet: install() has not run. "
                .'Setting the driver alone arms nothing — install() is what creates or refreshes each schedule, '
                .'and a schedule armed by an earlier process is not visible here.';
        }

        $ids = array_keys($this->armed);
        sort($ids);

        return sprintf(
            "\n%d schedule(s) armed by THIS process's install(): %s. This is not a query of the Temporal "
            .'server — a schedule armed by an earlier process is not counted here.',
            count($ids),
            implode(', ', $ids),
        ).$this->workerCoverage();
    }

    /**
     * The per-armed-type worker answer, as one block.
     *
     * THE FALSE SUCCESS THIS EXISTS TO KILL. `SdkTemporalScheduleClient` arms a
     * REAL server-side schedule whose action is `StartWorkflowAction::new($type)`
     * — a raw string. Before `TemporalWorker` shipped there was no worker in this
     * package at all, so every tick of an "armed" schedule started a workflow type
     * nothing implemented, and the status line said `armed %s recurring every
     * %ds` as though that were a working job. Before commit 729452d1 the driver
     * could only throw a NAMED RuntimeException; that loud honest failure was
     * replaced by a silent green one, which is the same defect one level up.
     *
     * So for every type THIS PROCESS armed, the text now says whether a worker in
     * this package serves it. `describe()` cannot answer for the whole server — it
     * holds no connection and dials nothing — so it answers only for what this
     * process armed, and says that much.
     *
     * A type with no answer at all (a third party's workflow string) is reported
     * as NOT served rather than omitted: silence there is the original defect.
     */
    private function workerCoverage(): string
    {
        if ($this->armedTypes === []) {
            return '';
        }

        $types = $this->armedTypes;
        sort($types);

        $lines = ["\nWorker coverage for the type(s) THIS process armed:"];

        foreach ($types as $type) {
            $lines[] = '  - '.$type.': '.$this->types->verdict($type);
        }

        return implode("\n", $lines);
    }

    /**
     * Whether a worker in this package serves a workflow type, and what to do
     * about it when it does not.
     *
     * Delegates to WorkflowTypeCatalog so the publisher's answer and the
     * worker's actual registration read from ONE registry — a second list here
     * would drift, and the drift is invisible until a tick fails.
     */
    private function disclosure(string $type): string
    {
        return $this->types->disclosure($type);
    }

    /**
     * The positive integer cadence from a spec entry, or null when absent /
     * non-positive. Zero or negative means "no cadence stated" — treating it
     * as a 0-second interval would arm a schedule that fires unthrottled.
     *
     * @param  array<string, mixed>  $spec
     */
    private function intervalOf(array $spec): ?int
    {
        $raw = $spec['interval_seconds'] ?? null;

        if ($raw === null || ! is_numeric($raw)) {
            return null;
        }

        $seconds = (int) $raw;

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * The workflow input for one spec entry: the entry's producer-keyed values,
     * minus the control keys this publisher consumes itself.
     *
     * WHY NOT JUST `$spec['args']`. `args` is the key the SDK client encodes, not
     * a key any producer in this tree emits — reading it alone is what armed
     * `telegram.app.run-call` schedules with an empty payload. `args` is still
     * honoured and merged first, so a third-party host that DOES set it keeps
     * working and can override.
     *
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private function payloadOf(array $spec): array
    {
        $payload = (array) ($spec['args'] ?? []);

        foreach ($spec as $key => $value) {
            $key = (string) $key;

            if (self::isControlKey($key)) {
                continue;
            }

            $payload[$key] = $value;
        }

        return $payload;
    }

    /**
     * Keys the publisher consumes itself and that are NOT the work's payload:
     * the cadence and the queue are already carried by the schedule/action the
     * SDK builds, and `activity` is the workflow TYPE (Temporal dispatches on the
     * string, not on a payload key) — re-sending it as input would be a second,
     * drifting copy of the truth. `args` is the pre-merged input, not a payload.
     */
    private static function isControlKey(string $key): bool
    {
        return $key === 'interval_seconds'
            || $key === 'activity'
            || $key === 'workflow'
            || $key === 'task_queue'
            || $key === 'args';
    }

    /**
     * The refusal a host actually needs to read: what is missing, and the one
     * thing it must bind. It does NOT claim temporal/sdk is a dependency of
     * this package — it is an OPTIONAL one (require-dev here), and the package
     * now supplies its own SDK-backed client, so reaching this message means
     * the client really is absent, not merely unset.
     */
    public static function missingClientMessage(int $scheduleCount = 0): string
    {
        return sprintf(
            'Schedule driver "temporal" is configured but no Temporal client is available, '
            .'refusing to silently drop %s. '
            .'Install the optional runtime dependency (composer require temporal/sdk; it is '
            .'require-dev here, on purpose) so the package can build its own SDK-backed '
            .'recurring client, or bind %s in the container yourself — '
            .'implement %s instead to arm RECURRING schedules '
            .'(a plain TemporalScheduleClient can only start a single run). '
            .'Until then use driver "null" with the documented cron line, or your own scheduler.',
            $scheduleCount === 1 ? '1 scheduled job' : $scheduleCount.' scheduled jobs',
            TemporalScheduleClient::class,
            RecurringScheduleClient::class,
        );
    }
}
