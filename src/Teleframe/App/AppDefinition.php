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
     * name and trigger/call lists whose elements are all AppTrigger /
     * AppCall instances, else InvalidArgumentException with a clear
     * message: config errors surface at declare time, never at dispatch.
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

            $seenCallNames[$call->name] = true;
            $validatedCalls[] = $call;
            $callIndex++;
        }

        return new self($name, $mode, $validatedTriggers, $validatedCalls);
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
