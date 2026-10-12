<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

/**
 * WorkflowTypeCatalog — the ONE place that knows which workflow type strings
 * this package's worker can actually serve.
 *
 * THE DEFECT THIS FILE EXISTS TO KILL. `SdkTemporalScheduleClient` arms a real
 * server-side Temporal schedule whose action is `StartWorkflowAction::new($type)`
 * — a RAW STRING. Before this catalog there was no worker at all, so a host
 * that set `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` (or the app / notify
 * equivalents) and installed `temporal/sdk` got a green status line —
 * `armed %s recurring every %ds` — for a schedule whose every tick started a
 * workflow type nothing implemented. That is a false success, the same shape
 * as the ones this cycle has been removing everywhere else: a class that
 * exists, a config key that is read, a test that constructs the object, and a
 * capability that is unreachable.
 *
 * So there are now TWO registries that must agree, and this class is the one
 * both read:
 *
 *   1. SdkTemporalScheduleClient arms a schedule whose WORKFLOW TYPE is the
 *      spec's `activity` string. Those strings are produced in exactly four
 *      places, and all four are accounted for here:
 *        - src/Teleframe/App/AppCallSpec.php:56  'telegram.app.run-call {app} {call}'   → SERVED
 *        - src/Teleframe/Store/Backup/BackupScheduleSpec.php:56  'teleframe:tstore backup'  → SERVED
 *        - src/Teleframe/Store/Backup/BackupScheduleSpec.php:62
 *          'teleclient.notify.check-no-show' → SERVED
 *        - src/Teleframe/Notify/NotifyCommand.php:230
 *          'teleframe.notify.check-no-show' → SERVED (a SECOND spelling of the same
 *          work; the two producers disagree — @see self::CHECK_NO_SHOW_ALIAS)
 *        - src/Teleframe/Store/Schedules/StoreMaintenanceScheduleSpec.php:129,133
 *          'teleframe:tstore prune' / 'teleframe:tstore sync'  → SERVED. These two were
 *          the whole content of self::ACKNOWLEDGED_UNSERVED — a KNOWN GAP, honestly
 *          reported, and still a hole: `teleframe:tstore prune` is a local
 *          argument-free `$cache->evict()` and `teleframe:tstore sync` is the dialog
 *          walk the command already runs, so a worker had no reason to serve
 *          neither. They are registered now (@see StorePruneWorkflow, StoreSyncWorkflow)
 *          and the list is EMPTY — which is a stronger position than a populated
 *          one, because a producer that arms an unserved type now has nothing to
 *          hide behind. @see self::ACKNOWLEDGED_UNSERVED for why the mechanism stays.
 *
 *   2. TemporalWorker::register() registers a workflow handler for every type
 *      this catalog says is served — and only those.
 *
 * WHY THE APP-CALL TYPE IS A FAMILY, NOT A CONSTANT. The string embeds the
 * app name and the call name, so the set is open-ended: it is exactly the set
 * of (app, call) pairs a host has DECLARED. A worker cannot enumerate that
 * from nothing — it is host configuration — so TemporalWorker takes the
 * declared pairs and registers one handler per pair. What the catalog can do,
 * and does, is decide whether a given string is WELL-FORMED for the family
 * (prefix + two non-empty tokens) without regex, which is what
 * `TemporalSchedulePublisher` uses to decide whether to say `served` or
 * `NOT served` in its status line.
 *
 * Plain-PHP, no container, no SDK symbol in any signature, zero regex.
 */
final class WorkflowTypeCatalog
{
    /**
     * The app-call family's type prefix. Kept as a const so the producer
     * (AppCallSpec) and this catalog cannot drift by a space.
     */
    public const APP_CALL_PREFIX = 'telegram.app.run-call';

    /** The store backup dump schedule's workflow type (BackupScheduleSpec). */
    public const STORE_BACKUP = 'teleframe:tstore backup';

    /** The no-show watchdog check's workflow type (BackupScheduleSpec). */
    public const CHECK_NO_SHOW = 'teleclient.notify.check-no-show';

    /**
     * The hot-cache prune schedule's workflow type (StoreMaintenanceScheduleSpec).
     *
     * WAS A KNOWN GAP, NOW SERVED. This type and TSTORE_SYNC were the two
     * entries ACKNOWLEDGED_UNSERVED carried, so arming them produced an honest
     * "every tick will fail to start a workflow" — honest, and still a hole:
     * `php artisan teleframe:tstore prune` is a real, local, argument-free
     * `$cache->evict()`, and there was no reason a worker could not call it.
     *
     * @see StorePruneWorkflow
     */
    public const TSTORE_PRUNE = 'teleframe:tstore prune';

    /**
     * The registry sweep schedule's workflow type (StoreMaintenanceScheduleSpec).
     *
     * WAS A KNOWN GAP, NOW SERVED — the registry walk the spec describes
     * (`StoreService::syncRegistry()`) is the same call the command makes.
     *
     * @see StoreSyncWorkflow
     */
    public const TSTORE_SYNC = 'teleframe:tstore sync';

    /**
     * The SECOND no-show workflow type — same work, different string.
     *
     * WHY TWO STRINGS FOR ONE ACTIVITY. The two no-show producers disagree
     * with each other and with themselves:
     *   - src/Teleframe/Store/Backup/BackupScheduleSpec.php:62 emits
     *     `teleclient.notify.check-no-show` (the historical `teleclient.*`
     *     prefix, which AGENTS.md keeps for Redis wire keys);
     *   - src/Teleframe/Notify/NotifyCommand.php:230 emits
     *     `teleframe.notify.check-no-show` (the `teleframe.*` prefix) — while
     *     its OWN docblock at line 46 still says `teleclient.notify.check-no-show`
     *     and its line 200 comment says yet another thing.
     *
     * That drift is a pre-existing inconsistency between two seams, owned by
     * two different slices, and it is NOT this worker's to settle. What IS this
     * worker's business is the consequence: whichever spelling is wrong, a
     * worker that serves only one of them leaves the other seam's schedule
     * ticking into nothing — the exact false success this class removes.
     *
     * So BOTH are registered, deliberately, and the drift is recorded here
     * rather than papered over. If a later slice unifies the two producers,
     * this constant goes with them.
     */
    public const CHECK_NO_SHOW_ALIAS = 'teleframe.notify.check-no-show';

    /**
     * Extra type strings an existing workflow class is ALSO registered under,
     * because a producer emits a different spelling of the same work.
     *
     * @return array<string, class-string>
     */
    public static function typeAliases(): array
    {
        return [
            self::CHECK_NO_SHOW_ALIAS => CheckNoShowWorkflow::class,
        ];
    }

    /** Separator between the type prefix and its two tokens. */
    private const SEPARATOR = ' ';

    /**
     * Schedule types the tree DOES arm, that this package's worker
     * deliberately does NOT serve, with the reason each one is left uncovered.
     *
     * WHY THIS LIST IS WORTH ITS OWN CONSTANT. The disclosure says "NOT
     * SERVED" for any type it does not recognise, which is correct but
     * indistinguishable from a type nobody thought about. Listing the known
     * uncovered producers here turns each one into a DOCUMENTED gap, and lets a
     * test enumerate the producers from source and fail on any type that is
     * neither served nor acknowledged. Without it, a fifth spec producer would
     * arm a schedule nothing implements and the only trace would be a status
     * line nobody reads.
     *
     * IT IS EMPTY, AND THAT IS THE POINT. It held `teleframe:tstore prune` and
     * `teleframe:tstore sync` until those two workflows were served; both are
     * now fixed types. The MECHANISM stays, because an empty list is not the
     * same property as a removed list:
     *
     *   - while it is empty, a producer that arms an unserved type fails
     *     `every_schedule_type_the_tree_produces_is_served_or_acknowledged_as_a_known_gap`
     *     on the commit that added it, with nothing acknowledged to hide behind;
     *   - the branch that renders a KNOWN GAP reason is still reachable, through
     *     the constructor's `$unserved` map, so the disclosure cannot rot into
     *     untested code the next time a gap is recorded.
     *
     * Do NOT delete this constant because it is empty, and do not record a gap
     * here to silence the producer test. Both turn the guard off.
     *
     * @var array<string, string>
     */
    private const ACKNOWLEDGED_UNSERVED = [];

    /**
     * @param  array<string, string>  $unserved  extra KNOWN-unserved types, keyed by workflow
     *                                           type, each with the reason to print. Merged over
     *                                           self::ACKNOWLEDGED_UNSERVED, and it can only make the
     *                                           disclosure LOUDER: an entry here keeps a type
     *                                           unserved (see serves()) and attaches a reason. It
     *                                           can never turn an unserved type into a served one, so
     *                                           this seam cannot be used to lie about coverage — it
     *                                           exists so the KNOWN-GAP branch stays exercisable
     *                                           while the shipped list is empty.
     */
    public function __construct(
        private readonly array $unserved = [],
    ) {}

    /**
     * Is this type a KNOWN-uncovered producer rather than a stranger?
     *
     * The verdict is "NOT SERVED" either way — a tick fails all the same — but
     * the REASON differs, and saying "this seam is deliberately not served yet"
     * is more useful to an operator than a bare "not served", which reads like a
     * typo in the spec.
     *
     * Both maps are consulted, the instance's first: a recorded gap in either
     * one makes the type a gap. Lookup walks the maps rather than indexing one,
     * so an EMPTY shipped list is a first-class state here rather than something
     * the code has to pretend is not empty.
     */
    public function acknowledgedUnservedReason(string $type): ?string
    {
        foreach ([$this->unserved, self::ACKNOWLEDGED_UNSERVED] as $gaps) {
            if (array_key_exists($type, $gaps)) {
                return $gaps[$type];
            }
        }

        return null;
    }

    /**
     * The types that are fixed strings: the backup/notify ones and the two
     * tstore maintenance ones. The app-call family is NOT listed here because it
     * has no fixed members —
     *
     * @see self::appCallType()
     *
     * @return list<string>
     */
    public static function fixedTypes(): array
    {
        return [
            self::STORE_BACKUP,
            self::CHECK_NO_SHOW,
            self::TSTORE_PRUNE,
            self::TSTORE_SYNC,
        ];
    }

    /**
     * The workflow type string AppCallSpec installs one call under.
     *
     * Deliberately the same shape AppCallSpec builds, and asserted equal to it
     * by a test — this is the mirror, not a second opinion.
     */
    public static function appCallType(string $app, string $call): string
    {
        return self::APP_CALL_PREFIX.self::SEPARATOR.$app.self::SEPARATOR.$call;
    }

    /**
     * The (app, call) pair a well-formed app-call type names, or null when the
     * string is not one.
     *
     * @return null|array{0: string, 1: string}
     */
    public function parseAppCall(string $type): ?array
    {
        if (! str_starts_with($type, self::APP_CALL_PREFIX.self::SEPARATOR)) {
            return null;
        }

        $tokens = explode(self::SEPARATOR, substr($type, strlen(self::APP_CALL_PREFIX) + 1));

        if (count($tokens) !== 2 || $tokens[0] === '' || $tokens[1] === '') {
            return null;
        }

        return [$tokens[0], $tokens[1]];
    }

    /**
     * Does a worker in THIS package serve `$type`?
     *
     * THE HONESTY PROPERTY. True for the fixed types, and for a
     * well-formed app-call type (the worker serves the family; which concrete
     * members exist is the host's declared (app, call) set, which the worker
     * registers — see TemporalWorker::appCallTypes()). False for everything
     * else, INCLUDING a well-formed-looking string from a third party: this
     * package does not implement another package's workflow.
     *
     * NOTE THE ORDER. An acknowledged reason is checked FIRST, so recording a
     * gap is the stronger statement: it keeps a type unserved even if that type
     * is also a fixed type or an alias, and it can never promote a stranger to
     * served. A gap can therefore only ever make the disclosure louder.
     */
    public function serves(string $type): bool
    {
        if ($this->acknowledgedUnservedReason($type) !== null) {
            return false;
        }

        if (in_array($type, self::fixedTypes(), true)) {
            return true;
        }

        if (array_key_exists($type, self::typeAliases())) {
            return true;
        }

        return $this->parseAppCall($type) !== null;
    }

    /**
     * The sentence TemporalSchedulePublisher appends to an armed schedule's
     * status. It names what the host must run, and — for an uncovered type —
     * says plainly that every tick will fail rather than leaving the green
     * `armed` line to imply otherwise.
     */
    public function disclosure(string $type, string $entryPoint = TemporalWorker::ENTRY_POINT): string
    {
        return 'worker: '.$this->verdict($type, $entryPoint);
    }

    /**
     * The same verdict without the `worker: ` label, for use under a heading
     * that already says which subject is being covered.
     */
    public function verdict(string $type, string $entryPoint = TemporalWorker::ENTRY_POINT): string
    {
        if ($this->serves($type)) {
            return sprintf(
                'served by this package — run %s (registers workflow type %s)',
                $entryPoint,
                $type,
            );
        }

        $known = $this->acknowledgedUnservedReason($type);

        return sprintf(
            'NOT SERVED by any worker in this package — %s implements no workflow type %s, '
            .'so every tick of this schedule will fail to start a workflow.%s '
            .'Serve it from your own worker, or do not arm it.',
            $entryPoint,
            $type,
            $known === null ? '' : ' KNOWN GAP: '.$known,
        );
    }

    /**
     * Every type string in the source tree that reaches
     * SdkTemporalScheduleClient::createOrUpdateSchedule(). Used by a test to
     * prove this catalog covers all of them — a fourth producer would otherwise
     * arm a type nothing serves, silently.
     *
     * @return list<string>
     */
    public static function producedTypes(): array
    {
        return [
            self::STORE_BACKUP,
            self::CHECK_NO_SHOW,
            self::TSTORE_PRUNE,
            self::TSTORE_SYNC,
            self::APP_CALL_PREFIX.self::SEPARATOR.'{app}'.self::SEPARATOR.'{call}',
        ];
    }
}
