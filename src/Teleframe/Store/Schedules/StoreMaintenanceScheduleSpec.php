<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

use InvalidArgumentException;

/**
 * StoreMaintenanceScheduleSpec — the tstore half of the install payload.
 *
 * WHY THIS FILE EXISTS. The `teleframe.tstore.schedules` seam could SELECT a
 * driver (ab1961bc) and nothing ever called `install()` on it, so a host who
 * set TELEFRAME_TSTORE_SCHEDULES=temporal got a describable publisher and no
 * job. This is the producer the seam was missing: it builds the payload
 * BackupScheduleSpec builds for the backup half, so `teleframe:tstore
 * schedules --install` has something real to hand through.
 *
 * It is the same shape as its sibling, deliberately, because a second shape
 * would be a second thing to learn: pure data, {interval_seconds, activity}
 * per entry, no container, no config, plain-PHP constructible.
 *
 * WHAT IS IN THE PAYLOAD — and what is deliberately NOT, with reasons. The
 * SchedulePublisher interface names three maintenance jobs ("the index
 * sweep, the hot-cache prune, and (for data channels) the media index").
 * Only two of them are recurring work that actually exists here:
 *
 *   tstore-hot-cache-prune  → `teleframe:tstore prune`
 *       StoreService::prune() is `$this->cache->evict()` — local, offline,
 *       argument-free, and it is the only thing that reclaims hot-cache
 *       bytes once their TTL passes. Recurring is exactly right: without a
 *       sweep the cache only shrinks when some other code path happens to
 *       evict.
 *
 *   tstore-registry-sweep   → `teleframe:tstore sync`
 *       syncRegistry() walks dialogs and upserts bucket rows, so a channel
 *       that was renamed or added is picked up without a human running the
 *       command. Needs the account's credentials (it is a dialog walk) —
 *       which is the operator's decision to arm, and why the default driver
 *       is still 'null'.
 *
 * EXCLUDED, NOT FORGOTTEN:
 *
 *   the media index. The `listen` key's own comment settles it: live
 *   indexing "as they land" is the listener's job, and reconciliation via
 *   the one-shot `teleframe:tstore index <bucket>` "is always available and
 *   exact". A recurring media-index job would be invented work for a
 *   reconciliation that is documented as a deliberate one-shot — and it
 *   cannot even be expressed as one schedule, because `index` takes a bucket
 *   argument and the bucket list lives in the store index (a database read),
 *   not in a payload a spec could name. Arming it would mean inventing a
 *   bucket-discovery contract this slice has no business inventing.
 *
 *   StreamSchema::STRIKES_KEY (tg:bus:strikes). Listed here because the
 *   brief named it, and it is NOT a sweep candidate: IngestConsumer sets an
 *   EXPIRE on that hash on every write (IngestConsumer.php:382, STRIKE_TTL),
 *   so a strike retires itself. A periodic prune would delete nothing that
 *   had not already expired, and a hash cannot be swept per-field anyway.
 *
 * CADENCES ARE CONSTRUCTOR ARGUMENTS, not config keys. The shipped default
 * driver is 'null', so these numbers only mean anything to someone who
 * deliberately armed; they are supplied on the arming command line
 * (`--every-prune`, `--every-sweep`) and defaulted here. A host wanting a
 * different cadence passes it; nothing is read from a config file this
 * slice may not edit, and no undeclared key is read — that mistake is
 * exactly what AppServiceProvider had to undo.
 *
 * IDEMPOTENCE IS THE CALLER'S, BY DESIGN. installSpec() is deterministic:
 * the same spec yields the same schedule ids, every time. That is what
 * makes the upsert in SdkTemporalScheduleClient an UPDATE on a re-arm
 * rather than a second schedule. Nothing here generates a fresh id, a
 * timestamp or a UUID — an id that changed per call would make re-arming
 * stack, which is the failure mode RecurringScheduleClient's contract
 * exists to prevent.
 *
 * Plain-PHP constructible, zero container, zero regex.
 */
final class StoreMaintenanceScheduleSpec
{
    /**
     * Hot-cache prune cadence. Hourly: comfortably above the shipped 600 s
     * cache TTL (so a prune is never pointless) and never faster than the
     * hourly floor the backup watchdog check already uses
     * (BackupScheduleSpec's min(interval × 2, 3600)).
     */
    public const DEFAULT_PRUNE_INTERVAL = 3600;

    /**
     * Registry sweep cadence. Daily: bucket rows change when an operator
     * renames or adds a channel, which is not a per-minute event, and each
     * tick is a dialog walk.
     */
    public const DEFAULT_SWEEP_INTERVAL = 86400;

    public function __construct(
        private readonly int $pruneIntervalSeconds = self::DEFAULT_PRUNE_INTERVAL,
        private readonly int $sweepIntervalSeconds = self::DEFAULT_SWEEP_INTERVAL,
    ) {
        // A non-positive cadence is not "the default" — it is an unthrottled
        // loop. Refuse at construction, where the operator's own input is in
        // scope, rather than letting it reach the client and throw from
        // inside an SDK call.
        $pruneIntervalSeconds > 0 or throw new InvalidArgumentException(sprintf(
            'hot-cache prune interval must be a positive number of seconds, got %d.',
            $pruneIntervalSeconds,
        ));
        $sweepIntervalSeconds > 0 or throw new InvalidArgumentException(sprintf(
            'registry sweep interval must be a positive number of seconds, got %d.',
            $sweepIntervalSeconds,
        ));
    }

    /**
     * The SchedulePublisher->install() payload for the tstore maintenance
     * half: {tstore-hot-cache-prune, tstore-registry-sweep}, each with
     * {interval_seconds, activity} — the exact shape BackupScheduleSpec
     * emits and the publisher reads.
     *
     * Safe to hand to a NullSchedulePublisher (the shipped default): it
     * records 'skipped (null schedule driver)' per entry without error.
     *
     * @return array<string, array{interval_seconds: int, activity: string}>
     */
    public function installSpec(): array
    {
        return [
            'tstore-hot-cache-prune' => [
                'interval_seconds' => $this->pruneIntervalSeconds,
                'activity' => 'teleframe:tstore prune',
            ],
            'tstore-registry-sweep' => [
                'interval_seconds' => $this->sweepIntervalSeconds,
                'activity' => 'teleframe:tstore sync',
            ],
        ];
    }

    /**
     * The one-schedule id per entry, for callers that need to name what
     * would be armed without arming it (`teleframe:tstore schedules`
     * prints this next to describe()).
     *
     * @return list<string>
     */
    public function scheduleIds(): array
    {
        return array_keys($this->installSpec());
    }

    /**
     * The human posture: what IS armable here, what is not, and the exact
     * command. The two excluded jobs are named so a reader who expected
     * them finds the reason instead of a silent gap.
     */
    public function describe(): string
    {
        return "Store maintenance schedules (tstore seam):\n"
            ."  tstore-hot-cache-prune  every {$this->pruneIntervalSeconds}s → teleframe:tstore prune\n"
            ."  tstore-registry-sweep   every {$this->sweepIntervalSeconds}s → teleframe:tstore sync\n"
            .'Not scheduled, on purpose: the media index is a one-shot reconciliation '
            .'(teleframe:tstore index <bucket> is exact, and teleframe.tstore.listen indexes as media lands), '
            ."and tg:bus:strikes expires per write, so there is nothing to sweep.\n"
            .'Arm them with: php artisan teleframe:tstore schedules --install '
            ."--every-prune={$this->pruneIntervalSeconds} --every-sweep={$this->sweepIntervalSeconds} "
            .'(needs teleframe.tstore.schedules=temporal).';
    }
}
