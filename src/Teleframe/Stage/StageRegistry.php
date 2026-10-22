<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage;

use InvalidArgumentException;

/**
 * Registry of active StageSets (Phase 5e). Mirrors the HandlerMatcher
 * constructor grammar — exact match, `prefix*`, catch-all `*`, single `%s`
 * placeholder — with ZERO regex (the framework-wide rule).
 *
 *   Registry order matters at match time: a set registered with a catch-all
 *   pattern shadows everything after it, matching the handler registry's
 *   first-match-wins semantics. Hosts register a catch-all FIRST, then the
 *   entry-anchored sets.
 *
 * On match, the resolver returns the StageSet itself; the StageMiddleware
 * independently confirms the flow is running before touching state, so a
 * registered-but-inactive set is never started by registry presence alone
 * (F5-strict: only active state is processed).
 */
final class StageRegistry
{
    /** @var array<int, array{set: StageSet, submit: ?callable}> */
    private array $records = [];

    private bool $compiled = false;

    /**
     * Register a compiled set. `$submit` (optional) short-circuits the Q20
     * sub-dispatch: when provided it is invoked with the validated data and
     * the middleware skips the FormRequest leg. With both absent a completed
     * flow sends the set's submitError template and keeps state (retryable).
     */
    public function on(StageSet $set, ?callable $submit = null): self
    {
        $this->assertMutable();

        $this->records[] = ['set' => $set, 'submit' => $submit];

        return $this;
    }

    /** Freeze the registry; `on()` afterwards throws. */
    public function compile(): self
    {
        foreach ($this->records as $record) {
            $record['set']->compile();
        }
        $this->compiled = true;

        return $this;
    }

    /** The StageSet whose name (entry constructor) matches $constructor, or null. */
    public function match(string $constructor): ?StageSet
    {
        foreach ($this->records as $record) {
            if ($this->patternMatches($record['set']->name(), $constructor)) {
                return $record['set'];
            }
        }

        return null;
    }

    /** Resolve a set by constructor pattern first, then by exact name. */
    public function setFor(string $constructor): ?StageSet
    {
        foreach ($this->records as $record) {
            if ($record['set']->name() === $constructor) {
                return $record['set'];
            }
        }

        return $this->match($constructor);
    }

    /**
     * Submit closure bound to a set: exact-name first, constructor pattern
     * second. Returns null when the set has no closure (route/FormRequest
     * leg then applies).
     */
    public function submitFor(string $constructor): ?callable
    {
        $set = $this->setFor($constructor);
        if ($set === null) {
            return null;
        }
        foreach ($this->records as $record) {
            if ($record['set'] === $set) {
                return $record['submit'];
            }
        }

        return null;
    }

    /** @return array<int, StageSet> */
    public function stageSets(): array
    {
        return array_map(static fn (array $record): StageSet => $record['set'], $this->records);
    }

    /**
     * Zero-regex constructor match, one pattern per rule:
     *
     *   - exact      "updateNewMessage"  → equal strings
     *   - prefix     "start*"            → $update starts with the prefix
     *   - catch-all  "*"                 → always matches
     *   - single %s  "confirm %s"        → sscanf single-token capture (the
     *                                      only placeholder allowed)
     */
    public function patternMatches(string $pattern, string $constructor): bool
    {
        if ($pattern === '*') {
            return true;
        }
        if (str_ends_with($pattern, '*')) {
            return str_starts_with($constructor, substr($pattern, 0, -1));
        }
        if (str_contains($pattern, '%s')) {
            $scanned = sscanf($constructor, $pattern);

            return is_array($scanned) && ($scanned[0] ?? null) !== null;
        }

        return $pattern === $constructor;
    }

    private function assertMutable(): void
    {
        if ($this->compiled) {
            throw new InvalidArgumentException('StageRegistry is already compiled; it is immutable.');
        }
    }
}