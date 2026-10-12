<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * NoShowWatchdog (design §2/G10, wave 2) — the gap detector: an expected
 * regular notification (the backup heartbeat on the 'backup' topic) that
 * does NOT arrive within its window means something is breaking, so
 * check() raises a WatchAlert for every overdue topic and routes it to
 * the dedicated `{topic}.missing` alert topic (ruling §6.5).
 *
 * Plain-PHP constructible, zero container calls:
 *   - $state  WatchState — durable PSR-16 last-seen (required seam;
 *             touch() records the heartbeat, check() reads it back)
 *   - $notify NotificationQueueContract — the alert sender (design §8:
 *             the alert action is Notify::to(...)->send(), which enqueues
 *             through the queue seam). Null = alerts are only returned +
 *             logged, never enqueued (offline-testable with fakes).
 *   - $now    Closure(): int — the injected clock (default time())
 *   - $logger PSR-3 — every alert is logged at alert level (NullLogger
 *             posture: silent when absent; logging never swallows)
 *
 * check() is detection-plus-emission through the injected seams only —
 * no Temporal, no network — so it stays offline-testable. Detection
 * rules (design §8): age = now - lastSeen; alert when lastSeen exists
 * AND age > expectEverySeconds. NO last-seen yet = grace period (a fresh
 * registration must not false-alarm on first deploy).
 *
 * REGISTRATION MUST SURVIVE THE PROCESS BOUNDARY. An in-memory
 * registration map can only work when registration and check() run in the
 * SAME process, which is never true of the owner's own example: the backup
 * run records the heartbeat in the process that dumps, and the no-show
 * check has to run in some other, later process. So a watchdog may also
 * carry a DERIVED registration source — a callable returning
 * topic ⇒ expected-every — which is re-read on every registrations()/
 * isRegistered()/check() call. The durable backup schedule spec is such a
 * source (StoreBackupServiceProvider), which is why the shipped backup
 * no-show works at all: the heartbeat is recorded in PSR-16 and the
 * expectation is derived from the same config the schedule install reads.
 *
 * Precedence: an in-memory register() row wins over a derived row for the
 * same topic (a host override is the more specific decision). A derived row
 * is validated exactly like a registered one — a registration IS a
 * decision, never silently misconfigured.
 *
 * A probe (callable(string): ?int) is the optional alternative observed
 * source: when it answers non-null for a topic, check() prefers it over
 * the state's last-seen. Zero regex — str_* and concatenation only.
 */
final class NoShowWatchdog
{
    /** @var array<string, array{expected_every: int, probe: ?callable}> topic ⇒ registration */
    private array $registrations = [];

    /**
     * @param  WatchState  $state  durable last-seen per watched topic
     * @param  NotificationQueueContract|null  $notify  the alert sender; null = alerts are only returned + logged
     * @param  \Closure(): int|null  $now  injected clock (default time())
     * @param  LoggerInterface|null  $logger  every alert is logged at alert level
     * @param  (\Closure(): array<array-key, mixed>)|null  $registrationSource  DERIVED registrations
     *                                                                         (topic ⇒ expected-every), re-read on
     *                                                                         every check so the expectation
     *                                                                         survives the process boundary
     */
    public function __construct(
        private readonly WatchState $state,
        private readonly ?NotificationQueueContract $notify = null,
        private readonly ?\Closure $now = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?\Closure $registrationSource = null,
    ) {}

    /**
     * Watch $topic for a heartbeat every $expectEverySeconds. Loud on
     * empty topics and non-positive intervals — a registration IS a
     * decision, never silently misconfigured. Returns nothing (design
     * §2 row: register/touch/check); hosts chain by calling again.
     *
     * @param  callable(string): ?int|null  $probe  optional alternative observed source
     */
    public function register(string $topic, int $expectEverySeconds, ?callable $probe = null): void
    {
        $topic = trim($topic);
        if ($topic === '') {
            throw new InvalidArgumentException('watchdog topic must not be empty.');
        }
        if ($expectEverySeconds <= 0) {
            throw new InvalidArgumentException('watchdog expectEverySeconds must be > 0, got '.$expectEverySeconds.'.');
        }

        $this->registrations[$topic] = [
            'expected_every' => $expectEverySeconds,
            'probe' => $probe,
        ];
    }

    /**
     * Record a heartbeat for $topic as observed now (design §8: every
     * send to a watched topic touches the state). Delegates to
     * WatchState — registration is not required, so the send path needs
     * no knowledge of the watchdog's config.
     */
    public function touch(string $topic): void
    {
        $this->state->touch($topic, $this->clock());
    }

    /**
     * Detect no-shows: for each registration, alert when the topic HAS a
     * last-seen AND its age exceeds the expected interval. No last-seen
     * yet = grace period, no alert. Every finding is emitted as a
     * WatchAlert through the injected seams (PSR-3 at alert level, then
     * enqueued on the `{topic}.missing` topic when a notify queue is
     * wired) and returned — hosts without a queue seam still get the
     * findings to act on.
     *
     * @return list<WatchAlert>
     */
    public function check(): array
    {
        $now = $this->clock();
        $alerts = [];

        foreach ($this->effectiveRegistrations() as $topic => $registration) {
            $lastSeen = $this->observed($topic, $registration);
            if ($lastSeen === null) {
                continue; // grace period — a fresh registration is not a false alarm
            }

            $age = $now - $lastSeen;
            if ($age <= $registration['expected_every']) {
                continue; // heartbeat arrived within the expected window
            }

            $alert = new WatchAlert($topic, $registration['expected_every'], $lastSeen, $age);
            $alerts[] = $alert;
            $this->emit($alert);
        }

        return $alerts;
    }

    /**
     * The effective registration map: the in-memory rows layered OVER the
     * derived source rows. Re-read on every call so a check in a fresh
     * process sees the same expectations the registering process had.
     *
     * @return array<string, array{expected_every: int, probe: ?callable}>
     */
    public function registrations(): array
    {
        return $this->effectiveRegistrations();
    }

    /** Whether $topic is currently watched — in memory OR by the derived source. */
    public function isRegistered(string $topic): bool
    {
        return isset($this->effectiveRegistrations()[$topic]);
    }

    /**
     * Layer the derived source under the in-memory rows, validating every
     * derived row exactly like register() does.
     *
     * @return array<string, array{expected_every: int, probe: ?callable}>
     */
    private function effectiveRegistrations(): array
    {
        $source = $this->registrationSource;
        if ($source === null) {
            return $this->registrations;
        }

        $rows = $this->registrations;

        foreach (($source)() as $topic => $expectEvery) {
            $topic = trim((string) $topic);
            if (isset($rows[$topic])) {
                continue; // an in-memory row is the more specific decision
            }
            if ($topic === '') {
                throw new InvalidArgumentException('watchdog topic must not be empty.');
            }
            $expect = is_numeric($expectEvery) ? (int) $expectEvery : 0;
            if ($expect <= 0) {
                throw new InvalidArgumentException(
                    'watchdog expectEverySeconds must be > 0, got '.get_debug_type($expectEvery).'.',
                );
            }

            $rows[$topic] = ['expected_every' => $expect, 'probe' => null];
        }

        return $rows;
    }

    /** The last observed heartbeat for $topic (WatchState passthrough — CLI status). */
    public function lastSeen(string $topic): ?int
    {
        return $this->state->lastSeen($topic);
    }

    /**
     * The observed timestamp for a topic: the probe's answer when it
     * returns one, else the state's last-seen.
     *
     * @param  array{expected_every: int, probe: ?callable}  $registration
     */
    private function observed(string $topic, array $registration): ?int
    {
        $probe = $registration['probe'];
        if ($probe !== null) {
            $observed = $probe($topic);
            if (is_int($observed)) {
                return $observed;
            }
        }

        return $this->state->lastSeen($topic);
    }

    /**
     * Emit one finding: PSR-3 at alert level (never swallows), then the
     * no-show notification on the dedicated `{topic}.missing` topic at
     * Level::Alert (design §8 / ruling §6.5) through the queue seam — a
     * target chat is NOT carried; the queue consumer resolves it from
     * the topic registry at consume time (loud when unconfigured, never
     * a silent drop).
     */
    private function emit(WatchAlert $alert): void
    {
        $context = [
            'topic' => $alert->topic,
            'expected_every_seconds' => $alert->expectedEvery,
            'last_seen' => $alert->lastSeen,
            'age_seconds' => $alert->age,
        ];

        try {
            $this->logger?->alert('notify watchdog: no update arrived for "'.$alert->topic.'"', $context);

            if ($this->notify === null) {
                return;
            }

            $this->notify->enqueue(new NotificationJob(
                jobId: 'tf-'.bin2hex(random_bytes(8)),
                topic: $alert->topic.'.missing',
                level: Level::Alert->value,
                brief: $alert->toNotify()['brief'],
                context: $context,
                premium: false,
                attempts: 0,
            ));
        } catch (Throwable $e) {
            // The alert pipeline must never die silently: log the
            // failure and rethrow — the caller (Temporal activity /
            // CLI --check) owns recovery, exactly like NotifySender.
            $this->logger?->error('notify watchdog: alert emission failed', [
                'topic' => $alert->topic,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    private function clock(): int
    {
        return ($this->now ?? static fn (): int => time())();
    }
}
