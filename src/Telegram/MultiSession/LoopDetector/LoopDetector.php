<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\LoopDetector;

use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoopDetector
{
    /** @var array<string, array<string, int>> */
    private array $actionGraph = [];

    /** @var array<string, array<int>> */
    private array $executionTimestamps = [];

    /** @var array<string, int> */
    private array $cooldownUntil = [];

    /** @var array<string, int> */
    private array $dedupWindows = [];

    private LoggerInterface $logger;

    private int $cycleWindowMs;

    private int $maxCycleLength;

    private int $defaultCooldownMs;

    private int $dedupWindowMs;

    public function __construct(
        int $cycleWindowMs = 30000,
        int $maxCycleLength = 10,
        int $defaultCooldownMs = 5000,
        int $dedupWindowMs = 5000,
    ) {
        $this->cycleWindowMs = $cycleWindowMs;
        $this->maxCycleLength = $maxCycleLength;
        $this->defaultCooldownMs = $defaultCooldownMs;
        $this->dedupWindowMs = $dedupWindowMs;
        $this->logger = new NullLogger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function recordExecution(string $actionId, ?string $triggeredBy = null): void
    {
        $now = $this->getCurrentTimeMs();

        // Record execution timestamp
        if (! isset($this->executionTimestamps[$actionId])) {
            $this->executionTimestamps[$actionId] = [];
        }
        $this->executionTimestamps[$actionId][] = $now;

        // Clean old timestamps
        $this->cleanOldTimestamps($actionId, $now);

        // Build action graph edge
        if ($triggeredBy !== null && $triggeredBy !== $actionId) {
            if (! isset($this->actionGraph[$triggeredBy])) {
                $this->actionGraph[$triggeredBy] = [];
            }
            $this->actionGraph[$triggeredBy][$actionId] = $now;
        }

        // Clean old graph edges
        $this->cleanOldGraphEdges($now);
    }

    public function wouldCreateCycle(string $actionId, string $proposedTrigger): bool
    {
        if ($actionId === $proposedTrigger) {
            return true; // Self-loop
        }

        // Check if adding edge proposedTrigger -> actionId would create a cycle
        // This means checking if there's already a path from actionId to proposedTrigger
        return $this->hasPath($actionId, $proposedTrigger);
    }

    public function hasPath(string $from, string $to, array $visited = []): bool
    {
        if ($from === $to) {
            return true;
        }

        if (in_array($from, $visited, true)) {
            return false; // Avoid infinite recursion
        }

        $visited[] = $from;

        if (! isset($this->actionGraph[$from])) {
            return false;
        }

        foreach ($this->actionGraph[$from] as $next => $timestamp) {
            if ($this->hasPath($next, $to, $visited)) {
                return true;
            }
        }

        return false;
    }

    public function getCycleReport(): array
    {
        $cycles = [];

        foreach ($this->actionGraph as $from => $edges) {
            foreach ($edges as $to => $timestamp) {
                if ($this->hasPath($to, $from)) {
                    $cycle = $this->extractCycle($from, $to);
                    if ($cycle !== null && count($cycle) <= $this->maxCycleLength) {
                        $cycles[] = new CycleReport(
                            cycle: $cycle,
                            length: count($cycle),
                            executionsInWindow: $this->getExecutionsInWindow($cycle),
                            severity: $this->calculateSeverity($cycle),
                            recommendedBreakers: $this->getRecommendedBreakers($cycle),
                        );
                    }
                }
            }
        }

        // Deduplicate cycles (same cycle detected from different starting points)
        $uniqueCycles = [];
        $seen = [];
        foreach ($cycles as $cycle) {
            $key = implode('->', $cycle->cycle);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueCycles[] = $cycle;
            }
        }

        return $uniqueCycles;
    }

    public function breakCycle(CycleReport $report): void
    {
        $now = $this->getCurrentTimeMs();

        foreach ($report->recommendedBreakers as $actionId) {
            $cooldownMs = $this->defaultCooldownMs;
            $this->cooldownUntil[$actionId] = $this->getCurrentTimeMs() + $cooldownMs;
            $this->dedupWindows[$actionId] = $this->dedupWindowMs;

            $this->logger->warning('LoopDetector: Breaking cycle', [
                'action_id' => $actionId,
                'cooldown_ms' => $cooldownMs,
                'cycle_length' => $report->length,
            ]);
        }

        // Remove edges that form the cycle
        foreach ($report->cycle as $actionId) {
            if (isset($this->actionGraph[$actionId])) {
                unset($this->actionGraph[$actionId]);
            }
        }
    }

    public function isInCooldown(string $actionId): bool
    {
        if (! isset($this->cooldownUntil[$actionId])) {
            return false;
        }
        if ($this->getCurrentTimeMs() >= $this->cooldownUntil[$actionId]) {
            unset($this->cooldownUntil[$actionId]);

            return false;
        }

        return true;
    }

    public function getCooldownRemainingMs(string $actionId): int
    {
        if (! isset($this->cooldownUntil[$actionId])) {
            return 0;
        }
        $remaining = $this->cooldownUntil[$actionId] - $this->getCurrentTimeMs();

        return $remaining > 0 ? $remaining : 0;
    }

    public function isDeduped(string $actionId): bool
    {
        if (! isset($this->dedupWindows[$actionId])) {
            return false;
        }

        if (! isset($this->executionTimestamps[$actionId])) {
            return false;
        }

        $now = $this->getCurrentTimeMs();
        $windowStart = $now - $this->dedupWindows[$actionId];

        $recentCount = 0;
        foreach ($this->executionTimestamps[$actionId] as $timestamp) {
            if ($timestamp >= $windowStart) {
                $recentCount++;
            }
        }

        // If executed in the dedup window, it's a duplicate
        return $recentCount >= 1;
    }

    public function shouldExecute(string $actionId, ?string $triggeredBy = null): bool
    {
        if ($this->isInCooldown($actionId)) {
            return false;
        }

        if ($this->isDeduped($actionId)) {
            return false;
        }

        if ($triggeredBy !== null && $this->wouldCreateCycle($actionId, $triggeredBy)) {
            return false;
        }

        return true;
    }

    public function getCooldown(string $actionId): int
    {
        return $this->getCooldownRemainingMs($actionId);
    }

    public function applyCooldown(string $actionId, int $cooldownMs): void
    {
        $this->cooldownUntil[$actionId] = $this->getCurrentTimeMs() + $cooldownMs;
    }

    public function applyPolicy(string $actionId, Policy $policy): void
    {
        if ($policy->cooldownMs > 0) {
            $this->cooldownUntil[$actionId] = $this->getCurrentTimeMs() + $policy->cooldownMs;
        }
        if ($policy->dedupWindowMs > 0) {
            $this->dedupWindows[$actionId] = $policy->dedupWindowMs;
        }
    }

    public function getExecutionsInWindow(array $cycle): int
    {
        $total = 0;
        $now = $this->getCurrentTimeMs();
        $windowStart = $now - $this->cycleWindowMs;

        foreach ($cycle as $actionId) {
            if (isset($this->executionTimestamps[$actionId])) {
                foreach ($this->executionTimestamps[$actionId] as $timestamp) {
                    if ($timestamp >= $windowStart) {
                        $total++;
                    }
                }
            }
        }

        return $total;
    }

    public function calculateSeverity(array $cycle): float
    {
        if (empty($cycle)) {
            return 0.0;
        }

        $executions = $this->getExecutionsInWindow($cycle);
        $length = count($cycle);
        $recentness = $this->getRecentnessScore($cycle);

        // Severity = 0.0 to 1.0
        $severity = (
            min(1.0, $executions / 10.0) * 0.4 +
            min(1.0, $length / 10.0) * 0.3 +
            $recentness * 0.3
        );

        return min(1.0, $severity);
    }

    public function getRecommendedBreakers(array $cycle): array
    {
        // Recommend breaking the action with highest execution count in cycle
        $counts = [];
        foreach ($cycle as $actionId) {
            $count = count($this->executionTimestamps[$actionId] ?? []);
            $counts[$actionId] = $count;
        }

        arsort($counts);

        return array_keys($counts);
    }

    private function extractCycle(string $from, string $to): ?array
    {
        $path = $this->findPath($to, $from, []);
        if ($path === null) {
            return null;
        }

        return array_merge([$from], $path);
    }

    private function findPath(string $from, string $to, array $visited): ?array
    {
        if ($from === $to) {
            return [];
        }

        if (in_array($from, $visited, true)) {
            return null;
        }

        $visited[] = $from;

        if (! isset($this->actionGraph[$from])) {
            return null;
        }

        foreach ($this->actionGraph[$from] as $next => $timestamp) {
            $subPath = $this->findPath($next, $to, $visited);
            if ($subPath !== null) {
                return array_merge([$next], $subPath);
            }
        }

        return null;
    }

    private function getRecentnessScore(array $cycle): float
    {
        $now = $this->getCurrentTimeMs();
        $maxAge = 0;

        foreach ($cycle as $actionId) {
            if (isset($this->executionTimestamps[$actionId])) {
                $latest = max($this->executionTimestamps[$actionId]);
                $age = $now - $latest;
                $maxAge = max($maxAge, $age);
            }
        }

        // Recentness: 1.0 = very recent (0ms), 0.0 = older than cycleWindowMs
        return max(0.0, 1.0 - ($maxAge / $this->cycleWindowMs));
    }

    private function cleanOldTimestamps(string $actionId, int $now): void
    {
        if (! isset($this->executionTimestamps[$actionId])) {
            return;
        }

        $threshold = $now - $this->cycleWindowMs;
        $this->executionTimestamps[$actionId] = array_filter(
            $this->executionTimestamps[$actionId],
            fn (int $ts) => $ts >= $threshold
        );
    }

    private function cleanOldGraphEdges(int $now): void
    {
        $threshold = $now - $this->cycleWindowMs;
        foreach ($this->actionGraph as $from => $edges) {
            $this->actionGraph[$from] = array_filter(
                $edges,
                fn (int $ts) => $ts >= $threshold
            );
            if (empty($this->actionGraph[$from])) {
                unset($this->actionGraph[$from]);
            }
        }
    }

    private function getCurrentTimeMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    public function getStats(): array
    {
        return [
            'graph_nodes' => count($this->actionGraph),
            'graph_edges' => array_reduce($this->actionGraph, fn ($c, $e) => $c + count($e), 0),
            'actions_tracked' => count($this->executionTimestamps),
            'cooldowns_active' => count(array_filter($this->cooldownUntil, fn ($t) => $t > $this->getCurrentTimeMs())),
            'dedup_windows_active' => count($this->dedupWindows),
            'cycles_detected' => count($this->getCycleReport()),
        ];
    }

    public function reset(): void
    {
        $this->actionGraph = [];
        $this->executionTimestamps = [];
        $this->cooldownUntil = [];
        $this->dedupWindows = [];
    }
}
