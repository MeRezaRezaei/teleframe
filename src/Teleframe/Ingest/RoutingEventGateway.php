<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Routing event gateway — the verbatim's "store the final truth, do NOT
 * make it a new event" boundary.
 *
 * Owner verbatim 2026-09-14: "when i make a notify channel for one of the
 * apps i should not handle the updates of that channel as new event but
 * only update that only stores the final truth... so we can controll the
 * things we are going to listen on or ignore in real time".
 *
 * The mirror ALWAYS stores the fact (one half of absolute truth); this
 * gateway decides whether the stored update ALSO becomes an app event
 * (second half). act_on → dispatch UpdateStored (handlers + observable
 * react); store_only → persist silently, no event — the notify/log channel
 * case that would otherwise loop until flood wait.
 *
 * The router consults the DB (or Redis-2 hot-reload cache) source of
 * truth, and it defaults every UNCLASSIFIED peer to store_only: nothing is
 * an app input until somebody opted in. Acting is therefore opt-in and
 * reachable — `teleframe:routing <id> --account-wide --mode=act_on` (or a
 * per-peer `--peer/--peer-id`) writes the opt-in row through the
 * UpdateRoutingRule model, whose observer hot-reloads it into the Redis
 * routing table. This docblock used to claim unclassified peers
 * default to act_on, which was the opposite of what the code did.
 *
 * ## THE DISPATCH OUTCOME, AND WHY IT IS NOT `dispatch()`'s RETURN VALUE
 *
 * The older docblock on this class reported a bool and said the listeners
 * "usually have not run yet" — a fact in prose that no caller could read.
 * `emitOutcome()` makes it readable. Getting there required measuring the
 * vendored framework rather than trusting the contract, and the measurement
 * contradicts the obvious reading, so the evidence is recorded here.
 *
 * `Illuminate\Events\Dispatcher::dispatch()` returns `array|null`. The three
 * `null` returns and the one array return, quoted:
 *
 *   - `if ($this->shouldDeferEvent($parsedEvent)) { ... return null; }`
 *     — Dispatcher.php:287-291, the dispatcher's OWN deferral list.
 *   - the `ShouldDispatchAfterCommit` branch: it calls
 *     `$transactions->addCallback(fn () => $this->invokeListeners(...))`
 *     and then `return null;` — Dispatcher.php:296-304.
 *   - `return $this->invokeListeners($parsedEvent, $parsedPayload, $halt);`
 *     — Dispatcher.php:306, the ONLY inline path, and the only one that
 *     yields an array (Dispatcher.php:345, `return $halt ? null : $responses`).
 *
 * So "returned null" does NOT mean "deferred". Measured against a real
 * `Illuminate\Events\Dispatcher` over `:memory:` SQLite with a real
 * `DatabaseTransactionsManager`:
 *
 *   no transaction open → `dispatch()` returns NULL **and the listener runs
 *   immediately**. `DatabaseTransactionsManager::addCallback()`
 *   (DatabaseTransactionsManager.php:213-220) is `if ($current =
 *   $this->callbackApplicableTransactions()->last()) { return
 *   $current->addCallback($callback); } $callback();` — with nothing pending
 *   it falls through and invokes the callback on the spot.
 *
 *   transaction open → `dispatch()` returns NULL, the listener log is EMPTY,
 *   and the manager's callback queue has grown by one.
 *
 * Both look identical through the return value. The queue is the difference,
 * which is why `pendingCallbackCount()` reads it and why the answer to "can
 * this layer know?" is YES for the routing decision and NO for the
 * post-commit result.
 *
 * WHAT IS KNOWABLE HERE, precisely:
 *   - whether the listener chain ran INSIDE this call — yes, an array return,
 *     or a `null` return with no queue growth;
 *   - whether it is waiting for a commit — yes, `null` return plus queue
 *     growth;
 *   - whether a dispatcher was bound at all — yes, the constructor's null
 *     check, which used to be prose and is now `dispatched => false`;
 *   - whether those listeners EVENTUALLY SUCCEEDED — NO. Nothing in this
 *     layer can report it. The callbacks live in
 *     `DatabaseTransactionRecord::$callbacks` (protected) and are executed by
 *     `executeCallbacks()` during the commit, outside every seam available
 *     here; the manager exposes no executed/ran accessor. `listeners_ran`
 *     therefore describes THIS call only, and `scheduled` deliberately
 *     promises nothing about what happens after the commit.
 *
 * A constant-null tri-state would have looked answerable and answered
 * nothing, which is exactly the defect this replaced. The unknown is now an
 * explicit OUTCOME_UNKNOWN rather than a confident wrong answer.
 */
final class RoutingEventGateway
{
    /** The routing decision was store_only; no event was even built. */
    public const OUTCOME_NOT_ROUTED = 'not_routed';

    /** act_on, but no dispatcher was bound — the handoff never happened. */
    public const OUTCOME_NO_DISPATCHER = 'no_dispatcher';

    /** The dispatcher invoked the listeners synchronously, inside this call. */
    public const OUTCOME_LISTENERS_RAN = 'listeners_ran';

    /** A post-commit callback now holds the dispatch; listeners have NOT run. */
    public const OUTCOME_SCHEDULED = 'scheduled_after_commit';

    /** The dispatch call threw; it was swallowed and logged. */
    public const OUTCOME_DISPATCH_FAILED = 'dispatch_failed';

    /**
     * The dispatch took a path this layer cannot classify. Deliberately NOT a
     * guess: `listeners_ran` and `scheduled` both stay false, because either
     * would be a fabrication. See `pendingCallbackCount()`.
     */
    public const OUTCOME_UNKNOWN = 'unknown';

    public function __construct(
        private readonly UpdateRouter $router,
        private readonly ?Dispatcher $events = null,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Store + conditionally emit. Returns whether the UpdateStored event
     * was dispatched (false = store_only peer, persisted silently).
     *
     * A pre-decided mode (e.g. from SelfOriginatedClassifier, which also
     * knows the out-flag group-2 default) short-circuits the per-peer
     * router lookup so the classifier and gateway can never disagree.
     *
     * THIS BOOL IS THE `emit_scheduled` CONTRACT AND IS DELIBERATELY UNCHANGED.
     * It is derived from `emitOutcome()`, and it means one thing only: the
     * update was ROUTED as an app input. It does NOT mean listeners ran, and
     * it does NOT mean a dispatcher was even bound. `emitOutcome()` is the
     * surface that distinguishes those; this stays a bool so every existing
     * caller keeps working.
     *
     * @param  array<string, mixed>  $payload  decoded TL update
     * @param  Model  $model  the persisted root model
     * @param  string|null  $decidedMode  UpdateRoutingRule::MODE_* decision, or null to ask the router
     */
    public function emit(int $accountId, array $payload, Model $model, ?string $decidedMode = null): bool
    {
        return $this->emitOutcome($accountId, $payload, $model, $decidedMode)['routed'];
    }

    /**
     * The same decision as `emit()`, reported as WHAT ACTUALLY HAPPENED
     * rather than as a single bool — the surface the `emit_scheduled`
     * docblock names as missing.
     *
     * @return array{
     *     routed: bool,
     *     dispatched: bool,
     *     listeners_ran: bool,
     *     scheduled: bool,
     *     outcome: self::OUTCOME_*,
     * }
     *
     * KEY MEANINGS, each one earned by a vendor line (see class docblock):
     *
     *   routed        — the store_only/act_on decision, act_on. This is
     *                   exactly what `emit()` returns and the meaning
     *                   `emit_scheduled` carries.
     *   dispatched    — an UpdateStored object was handed to a dispatcher.
     *                   False when nothing was bound, so the "conditional
     *                   on a dispatcher existing" fact becomes DERIVABLE
     *                   instead of only stated in prose.
     *   listeners_ran — the dispatcher invoked the listeners synchronously
     *                   DURING THIS CALL. Says nothing about whether any of
     *                   them succeeded; a throwing listener is swallowed and
     *                   logged, and that is unchanged by this method.
     *   scheduled     — a post-commit callback now holds this dispatch. The
     *                   listeners have NOT run yet, and their eventual
     *                   outcome is NOT knowable from this layer at all.
     *   outcome       — one of the OUTCOME_* constants, so a caller that
     *                   wants one word gets exactly one word.
     */
    public function emitOutcome(int $accountId, array $payload, Model $model, ?string $decidedMode = null): array
    {
        $mode = $decidedMode ?? $this->router->classify($accountId, $payload);

        if ($mode === UpdateRoutingRule::MODE_STORE_ONLY) {
            return $this->outcome(self::OUTCOME_NOT_ROUTED, false);
        }

        $events = $this->events;

        if ($events === null) {
            return $this->outcome(self::OUTCOME_NO_DISPATCHER, true);
        }

        return $this->dispatchStored($events, $accountId, $payload, $model);
    }

    /**
     * @return array{routed: bool, dispatched: bool, listeners_ran: bool, scheduled: bool, outcome: self::OUTCOME_*}
     */
    private function outcome(string $outcome, bool $routed, bool $dispatched = false, bool $listenersRan = false, bool $scheduled = false): array
    {
        return [
            'routed' => $routed,
            'dispatched' => $dispatched,
            'listeners_ran' => $listenersRan,
            'scheduled' => $scheduled,
            'outcome' => $outcome,
        ];
    }

    /**
     * Hand the stored update to the dispatcher, report which of the five
     * outcomes it reached, and NEVER let a listener failure escape back
     * into ingest.
     *
     * The catch is scoped to the dispatch call and nothing else: the routing
     * decision above, the return contract, and every write the caller
     * performs all stay outside the try. A wiring mistake therefore still
     * surfaces as a logged `error` carrying the account, the model class and
     * key, the update constructor, and the exception class/message — never
     * as silence.
     *
     * Why swallowing is safe HERE and only because of Fix 1:
     * UpdateStored implements ShouldDispatchAfterCommit, so this dispatch
     * either (a) registers a post-commit callback and returns without
     * running a single listener, or (b) runs listeners inline only when
     * there is no transaction to wait for. In the mirror path — the one
     * path that used to call emit() from inside an open transaction — the
     * listeners now run after the mirror facts are committed. A listener
     * throwing can therefore no longer roll back a stored row; letting the
     * exception escape would instead abort the bus consumer, which strikes
     * the entry and REDELIVERS work that is already committed — trading a
     * lost write for duplicated side effects (Centrifugo publish, tstore
     * append) on every retry, forever.
     *
     * The dead-letter half of this already existed downstream:
     * `Handler\UpdateDispatcher` tags the failure for the DL stream BEFORE
     * it rethrows ("the seam is a tag, not a swallow"). This method is the
     * missing swallow, and it sits deliberately downstream of that tag.
     *
     * HOW THE OUTCOME IS DERIVED, and why `dispatch()`'s own return value
     * cannot carry it alone. `Illuminate\Events\Dispatcher::dispatch()`
     * returns `null` on the deferral path — but it ALSO returns `null` when
     * the deferral path finds no open transaction and therefore runs the
     * listener inline. `null` is genuinely ambiguous between "deferred" and
     * "already ran", so the discriminator is not the return value at all; it
     * is whether the transaction manager's callback queue GREW across the
     * call. See `pendingCallbackCount()` for the measured evidence.
     *
     * @return array{routed: bool, dispatched: bool, listeners_ran: bool, scheduled: bool, outcome: self::OUTCOME_*}
     */
    private function dispatchStored(Dispatcher $events, int $accountId, array $payload, Model $model): array
    {
        $this->capableOfDeferring($events);

        $pendingBefore = $this->pendingCallbackCount($events);

        try {
            $result = $events->dispatch(new UpdateStored($model, $accountId));
        } catch (Throwable $e) {
            $this->logger?->error('teleframe: UpdateStored dispatch failed after the update was stored; listeners were skipped', [
                'account' => $accountId,
                'model' => $model::class,
                'model_key' => $model->getKey(),
                'constructor' => (string) ($payload['_'] ?? ''),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return $this->outcome(self::OUTCOME_DISPATCH_FAILED, true);
        }

        // An ARRAY is unambiguous, and needs no reflection at all: it is the
        // return of `invokeListeners()` (Dispatcher.php:306), i.e. the whole
        // listener chain ran inside this call. `dispatch()` reaches that line
        // whenever there is no deferral to perform.
        if (is_array($result)) {
            return $this->outcome(self::OUTCOME_LISTENERS_RAN, true, true, true);
        }

        // `null` from here on means the AFTER-COMMIT BRANCH ran. Whether the
        // listeners ran anyway is decided by `addCallback()`: it queues on
        // the applicable pending transaction, or — when there is none — calls
        // the callback straight away. So the queue's growth is the fact.
        if ($pendingBefore === null) {
            return $this->outcome(self::OUTCOME_UNKNOWN, true, true);
        }

        $pendingAfter = $this->pendingCallbackCount($events);

        if ($pendingAfter === null) {
            return $this->outcome(self::OUTCOME_UNKNOWN, true, true);
        }

        if ($pendingAfter > $pendingBefore) {
            return $this->outcome(self::OUTCOME_SCHEDULED, true, true, false, true);
        }

        // No growth, so `addCallback()` found no applicable transaction and
        // invoked the listener inline — MEASURED, not inferred.
        //
        // The one path this cannot speak for is `Dispatcher::dispatch()`'s
        // OTHER null return, the `shouldDeferEvent()` branch, which parks the
        // event on the dispatcher's own `deferredEvents` list instead of the
        // transaction manager. That list is invisible to the queue count, so
        // while deferring is switched on the honest answer is `unknown`
        // rather than a confident `listeners_ran` that may be false.
        if ($this->isDeferringEvents($events)) {
            return $this->outcome(self::OUTCOME_UNKNOWN, true, true);
        }

        return $this->outcome(self::OUTCOME_LISTENERS_RAN, true, true, true);
    }

    /**
     * Give a hand-built Illuminate dispatcher the null transaction-manager
     * resolver it is missing, so `ShouldDispatchAfterCommit` degrades to an
     * inline dispatch instead of a fatal.
     *
     * ## WHY THIS IS NEEDED AND NOT PARANOIA
     *
     * `Illuminate\Events\Dispatcher` declares `protected $transactionManagerResolver;`
     * with NO default, and `resolveTransactionManager()` is
     * `call_user_func($this->transactionManagerResolver)`. The framework's own
     * `EventServiceProvider` always installs one — `fn () => app()->bound(
     * 'db.transactions') ? app('db.transactions') : null` — but a plain
     * `new Dispatcher` (the documented way to build a non-Laravel host's own
     * dispatcher, and what several tests in this repo do) leaves it null.
     * `call_user_func(null)` is a `TypeError`, so with the marker on the event
     * that TypeError fires for EVERY dispatch, and because Fix 2 catches
     * around this call it would be swallowed into a log line: the stored
     * truth would commit and the listeners would silently never run — a
     * strictly worse version of the bug this change set out to close.
     *
     * Returning `null` is the framework's own answer for "no transaction
     * manager": the guard in `Dispatcher::dispatch()` is
     * `! is_null($transactions = $this->resolveTransactionManager())`, so a
     * null result falls straight through to `invokeListeners()`. That is the
     * correct semantics for a dispatcher with no transaction to wait for, and
     * it is byte-identical to this gateway's behaviour before the marker.
     *
     * Only ever installs when the slot is empty, so a host that DID configure
     * a resolver keeps its own. If a future framework renames the property
     * the reflection throws, is swallowed, and the dispatch then fails
     * loudly through the catch above with the TypeError in the log — a
     * visible failure rather than a silent one.
     */
    private function capableOfDeferring(Dispatcher $events): void
    {
        if (! $events instanceof \Illuminate\Events\Dispatcher) {
            return; // a third-party dispatcher has its own contract to honour
        }

        try {
            $resolver = new \ReflectionProperty($events, 'transactionManagerResolver');

            if ($resolver->getValue($events) === null) {
                $events->setTransactionManagerResolver(static fn (): null => null);
            }
        } catch (\ReflectionException) {
            // No such property on this framework version: leave the
            // dispatcher's own behaviour alone and let it decide.
        }
    }

    /**
     * How many post-commit callbacks the dispatcher's transaction manager is
     * currently holding, or null when that cannot be read.
     *
     * This is the ONLY observable that separates "deferred to commit" from
     * "already ran inline", because `dispatch()` returns `null` for both.
     * Measured against the vendored framework (see the class docblock):
     *
     *   open transaction → `DatabaseTransactionsManager::addCallback()`
     *   finds an applicable record, appends, and `dispatch()` returns null
     *   while the listener log stays EMPTY.  Count grows by 1.
     *
     *   no transaction → the same `addCallback()` finds no applicable record,
     *   falls through to `$callback()`, and runs the listener IMMEDIATELY.
     *   `dispatch()` STILL returns null.  Count does not move.
     *
     * So the return value alone is a guess and this count is the fact.
     *
     * `null` is returned, never a guess, when the shape is unfamiliar: a
     * non-Illuminate dispatcher, a renamed/missing property, a resolver that
     * throws, a manager without `callbackApplicableTransactions()`, or a
     * callback record without `getCallbacks()`. Every one of those yields
     * OUTCOME_UNKNOWN upstream instead of a confident wrong answer.
     */
    private function pendingCallbackCount(Dispatcher $events): ?int
    {
        if (! $events instanceof \Illuminate\Events\Dispatcher) {
            return null;
        }

        try {
            $resolver = new \ReflectionProperty($events, 'transactionManagerResolver');
            $factory = $resolver->getValue($events);

            if (! is_callable($factory)) {
                return null;
            }

            $manager = $factory();

            if (! is_object($manager) || ! method_exists($manager, 'callbackApplicableTransactions')) {
                return null;
            }

            $records = $manager->callbackApplicableTransactions();

            if (! is_iterable($records)) {
                return null;
            }

            $count = 0;

            foreach ($records as $record) {
                if (! is_object($record) || ! method_exists($record, 'getCallbacks')) {
                    return null;
                }

                $callbacks = $record->getCallbacks();

                if (! is_countable($callbacks)) {
                    return null;
                }

                $count += count($callbacks);
            }

            return $count;
        } catch (Throwable) {
            // A renamed property, or a resolver that throws. Unknown, not
            // false: the caller reports OUTCOME_UNKNOWN rather than a guess.
            return null;
        }
    }

    /**
     * Is the dispatcher's OWN event deferral switched on? While it is, a
     * `null` return may mean `shouldDeferEvent()` parked the event on
     * `deferredEvents` rather than `addCallback()` queuing it — and that list
     * is invisible to `pendingCallbackCount()`, so the caller must be told
     * `unknown` instead of `listeners_ran`.
     *
     * Reflected rather than called because `shouldDeferEvent()` is
     * `protected`; the backing `deferringEvents` flag is `protected` too.
     */
    private function isDeferringEvents(Dispatcher $events): bool
    {
        try {
            return (bool) (new \ReflectionProperty($events, 'deferringEvents'))->getValue($events);
        } catch (Throwable) {
            // Cannot read the flag. Answering `true` would report `unknown`
            // for the common case, which is louder than it is honest; the
            // flag is only set by an explicit `defer()` this gateway never
            // calls, so the safe default is "not deferring".
            return false;
        }
    }
}
