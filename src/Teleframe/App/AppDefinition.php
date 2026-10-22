<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

/**
 * AppDefinition (design §2/G1, ctor sketch §9) — the immutable declarative
 * program: a name, an AppMode (bot|user), a trigger list (chat → handler
 * in the SHIPPED matcher grammar) and a call list (scheduled telegram
 * calls). Plain data only — no container, no facade, standalone-smoke-
 * green; sessions/tokens never appear (credentials are resolved at call
 * time by the transport seam). AppResponder installs triggers() onto the
 * shared HandlerRegistry; AppCallSpec composes calls() into
 * SchedulePublisher payloads.
 */
final class AppDefinition
{
    /**
     * The app-call workflow type's token separator — the ONE character a declared
     * app or call name may not carry, because that type embeds both names as
     * space-delimited tokens and is parsed back into the (app, call) pair.
     */
    private const WIRE_SEPARATOR = ' ';

    /**
     * @param  list<AppTrigger>  $triggers
     * @param  list<AppCall>  $calls
     */
    private function __construct(
        public readonly string $name,
        public readonly AppMode $mode,
        public readonly array $triggers = [],
        public readonly array $calls = [],
    ) {}

    /**
     * Declare a program (design §9). Validation is loud — a non-empty
     * name, trigger/call lists whose elements are all AppTrigger /
     * AppCall instances, and names the app-call WORKFLOW TYPE could
     * never carry, else InvalidArgumentException with a clear message:
     * config errors surface at declare time, never at dispatch.
     *
     * @param  array  $triggers  declared trigger rows — every element must be an AppTrigger
     * @param  array  $calls  declared call rows — every element must be an AppCall
     */
    public static function define(string $name, AppMode $mode, array $triggers = [], array $calls = []): self
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException(sprintf('app name must be a non-empty string, got "%s"', $name));
        }

        $validatedTriggers = [];
        $triggerIndex = 0;
        foreach ($triggers as $trigger) {
            if (! $trigger instanceof AppTrigger) {
                throw new \InvalidArgumentException(sprintf(
                    'app [%s] trigger list may only contain AppTrigger instances, got %s at index %d',
                    $name,
                    get_debug_type($trigger),
                    $triggerIndex,
                ));
            }
            $validatedTriggers[] = $trigger;
            $triggerIndex++;
        }

        $validatedCalls = [];
        $seenCallNames = [];
        $callIndex = 0;
        foreach ($calls as $call) {
            if (! $call instanceof AppCall) {
                throw new \InvalidArgumentException(sprintf(
                    'app [%s] call list may only contain AppCall instances, got %s at index %d',
                    $name,
                    get_debug_type($call),
                    $callIndex,
                ));
            }

            // CALL NAMES ARE UNIQUE WITHIN AN APP, AND SAY SO AT DECLARE TIME.
            //
            // AppCallSpec::installSpec() keys the schedule payload by
            // callId(app, call) — a function of the (app, call) PAIR — so two
            // declarations of one name inside one app collide on the SAME key and
            // the second overwrites the first. Nothing reported it: an app
            // declaring `status-ping` at 3600s and then again at 60s installed
            // ONE schedule, at 60s, and `teleframe:app calls` printed that one row
            // as if it were the whole declaration. The 3600s call was gone,
            // silently.
            //
            // Cross-app reuse of a name is FINE and stays fine — that is what the
            // digest half of the id exists for, and it was already fixed. This is
            // only the within-app case, where there is no id to tell the two calls
            // apart at all.
            if (array_key_exists($call->name, $seenCallNames)) {
                throw new \InvalidArgumentException(sprintf(
                    'app [%s] declares two calls named [%s]; a call id is unique within its app',
                    $name,
                    $call->name,
                ));
            }

            // A CALL NAME IS THE SECOND TOKEN OF THE WIRE TYPE. It exists only to be
            // named there, so it may not hold the separator — see
            // self::refuseUnrepresentableName().
            self::refuseUnrepresentableName($name, 'call name', $call->name);

            $seenCallNames[$call->name] = true;
            $validatedCalls[] = $call;
            $callIndex++;
        }

        // AN APP NAME IS THE FIRST TOKEN OF THE WIRE TYPE — but only once the app
        // declares a call. Without one, no type string is ever built for this app
        // (the name is a registry key, an `app:{name}` tag prefix and a CLI row
        // label, and a space is harmless in all three), so refusing it there would
        // take down a declaration that works, over a defect it cannot reach — and
        // from provider boot(), for every process.
        if ($validatedCalls !== []) {
            self::refuseUnrepresentableName($name, 'name', $name);
        }

        return new self($name, $mode, $validatedTriggers, $validatedCalls);
    }

    /**
     * A DECLARED NAME MUST BE REPRESENTABLE ON THE WIRE (F16-4) — refuse the one
     * character that makes the (app, call) pair unrecoverable from the workflow type
     * both producers build and the catalog parses back.
     *
     * THE DEFECT, MEASURED. A scheduled call names itself as ONE space-delimited
     * string: AppCallSpec::installSpec() concatenates `telegram.app.run-call {app}
     * {call}` and arms the schedule with it; WorkflowTypeCatalog::parseAppCall()
     * explodes it on the separator and requires exactly two non-empty tokens. A name
     * carrying the separator broke that round trip in TWO ways, and reported neither:
     *
     *   1. THE FALSE RED. The worker's TemporalWorker::appCallTypes() registers the
     *      very same string for the pair the operator passed on the command line, and
     *      the tick really does run — the activity reads `app`/`call` off the PAYLOAD
     *      (AppRunCallWorkflow), not out of the type string. But the catalog could
     *      not parse the type back, so serves() said false and the publisher printed
     *      "NOT SERVED by any worker in this package … every tick of this schedule
     *      will fail to start a workflow" about a worker that had ALREADY registered
     *      it: an operator sent to debug a green worker.
     *   2. THE AMBIGUITY, WHICH IS WHY NO DOWNSTREAM FIX EXISTS. ('my app',
     *      'status-ping') and ('my', 'app status-ping') render the IDENTICAL type
     *      string, so the pair is not recoverable from it — not by exploding it, not
     *      by splitting from the ends. Once a name holds the separator the
     *      (app, call) -> type mapping stops being injective, a worker started for
     *      one pair serves the other's type, and AppCallSpec's digest cannot help
     *      because it guards the schedule ID, not the type. This is the same
     *      collision class the digest was added for, one field the digest does not
     *      reach — and the audit's second option (parse from the ends) is not a fix,
     *      it is the ambiguity restated.
     *
     * So the name is refused HERE: at declare time, on the row that caused it, before
     * any schedule is armed — instead of in a status line later that names a worker
     * which is already running. The exception is this class's own, alongside the blank
     * name, the non-AppTrigger element and the within-app duplicate call name.
     *
     * NO TRIMMING, DELIBERATELY. A leading or trailing space produces the same
     * unrepresentable type (two separators in a row), and it is refused rather than
     * normalised away: every consumer prints a name verbatim — the payload's `app`
     * and `call` keys, the `app:{name}` tag, `teleframe:app list` — so silently
     * rewriting the row the host wrote would make the CLI stop echoing back what was
     * declared, and would collapse two distinct rows onto one AppRegistry key.
     */
    /**
     * Assert that an (app, call) pair can be named on the wire.
     *
     * WHY THIS IS PUBLIC AND SEPARATE. self::define() refuses an
     * unrepresentable name, but a worker is not handed app definitions — it is
     * handed raw (app, call) pairs, e.g. from `bin/teleframe-worker
     * --app=name --call=name`. Nothing on that path went through define(), so
     * nothing on it carried this refusal, and a pair whose name holds the
     * separator reached the worker unvalidated. The worker then registers a
     * workflow type it can never serve, and reports the schedule NOT SERVED by
     * a worker it had already registered — which is exactly the
     * unrecoverable-round-trip failure define() was fixed to refuse.
     *
     * Exposing the check rather than duplicating it keeps ONE definition of
     * "representable": a name accepted here and a name accepted by define() can
     * never disagree.
     *
     * @throws \InvalidArgumentException when either name is blank or holds the wire separator
     */
    public static function assertPairRepresentable(string $app, string $call): void
    {
        if (trim($app) === '') {
            throw new \InvalidArgumentException(sprintf('app name must be a non-empty string, got "%s"', $app));
        }

        if (trim($call) === '') {
            throw new \InvalidArgumentException(sprintf('call name for app [%s] must be a non-empty string, got "%s"', $app, $call));
        }

        self::refuseUnrepresentableName($app, 'name', $app);
        self::refuseUnrepresentableName($app, 'call name', $call);
    }

    private static function refuseUnrepresentableName(string $app, string $subject, string $name): void
    {
        if (! str_contains($name, self::WIRE_SEPARATOR)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'app [%s] %s [%s] may not contain a space: a scheduled call names itself on the wire as ONE '
            .'space-delimited workflow type (AppCallSpec assembles it, WorkflowTypeCatalog::parseAppCall() '
            .'parses it back), so this name cannot be represented and the declared pair cannot be recovered '
            .'from the type — a worker started for another pair would serve this schedule. Left as is, the '
            .'schedule for this call is reported NOT SERVED by a worker this package had already registered. '
            .'Rename it (e.g. [%s]).',
            $app,
            $subject,
            $name,
            str_replace(self::WIRE_SEPARATOR, '-', $name),
        ));
    }

    /**
     * The first trigger whose match equals the given string exactly, else
     * null — first-match-wins, the same posture as the shipped matcher.
     */
    public function triggerFor(string $match): ?AppTrigger
    {
        foreach ($this->triggers as $trigger) {
            if ($trigger->match === $match) {
                return $trigger;
            }
        }

        return null;
    }

    /**
     * The trigger list — AppResponder::install loops this (design §4).
     *
     * @return list<AppTrigger>
     */
    public function triggers(): array
    {
        return $this->triggers;
    }

    /**
     * The scheduled-call list — AppCallSpec::installSpec loops this
     * (design §5).
     *
     * @return list<AppCall>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    /**
     * The declarative row (registry list / AppCommand previews): mode as
     * its backed word, triggers/calls as their toArray() rows.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $triggerRows = [];
        foreach ($this->triggers as $trigger) {
            $triggerRows[] = $trigger->toArray();
        }

        $callRows = [];
        foreach ($this->calls as $call) {
            $callRows[] = $call->toArray();
        }

        return [
            'name' => $this->name,
            'mode' => $this->mode->value,
            'triggers' => $triggerRows,
            'calls' => $callRows,
        ];
    }
}
