<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession\LoopDetector;

use PHPUnit\Framework\TestCase;

class LoopDetectorTest extends TestCase
{
    public function testRecordExecutionAndShouldExecute(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        $actionId = 'test_action';

        // Apply default dedup policy
        $policy = new \MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy(
            cooldownMs: 0,
            dedupWindowMs: 5000,
            maxExecutions: -1,
            breakCycles: true,
            cycleBreakers: []
        );
        $detector->applyPolicy($actionId, $policy);

        // First execution should be allowed
        $this->assertTrue($detector->shouldExecute($actionId));
        $detector->recordExecution($actionId);

        // Second execution within dedup window should be blocked
        $this->assertFalse($detector->shouldExecute($actionId));
    }

    public function testCooldown(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 1000,
            dedupWindowMs: 5000
        );

        $actionId = 'cooldown_action';

        // Execute and apply cooldown
        $detector->recordExecution($actionId);
        $detector->applyCooldown($actionId, 1000);

        // Should be in cooldown
        $this->assertTrue($detector->isInCooldown($actionId));
        $this->assertFalse($detector->shouldExecute($actionId));

        // Wait for cooldown to expire
        usleep(1100000); // 1.1 seconds
        $this->assertFalse($detector->isInCooldown($actionId));
        $this->assertTrue($detector->shouldExecute($actionId));
    }

    public function testCycleDetection(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        // Create a cycle: A -> B -> C -> A
        $detector->recordExecution('A', null);
        $detector->recordExecution('B', 'A');
        $detector->recordExecution('C', 'B');
        $detector->recordExecution('A', 'C'); // This creates a cycle

        $cycles = $detector->getCycleReport();
        $this->assertNotEmpty($cycles);

        // Check that at least one cycle is detected
        $foundCycle = false;
        foreach ($cycles as $cycle) {
            // The cycle might be extracted differently, just check we have a cycle
            if (count($cycle->cycle) >= 3) {
                $foundCycle = true;
                break;
            }
        }
        $this->assertTrue($foundCycle, 'A cycle should be detected');
    }

    public function testWouldCreateCycle(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        // Create path A -> B -> C
        $detector->recordExecution('A', null);
        $detector->recordExecution('B', 'A');
        $detector->recordExecution('C', 'B');

        // Adding C -> A would create a cycle
        $this->assertTrue($detector->wouldCreateCycle('A', 'C'));

        // Adding C -> D would not create a cycle
        $this->assertFalse($detector->wouldCreateCycle('D', 'C'));
    }

    public function testSelfLoop(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        // Self-loop should be detected
        $this->assertTrue($detector->wouldCreateCycle('A', 'A'));
    }

    public function testBreakCycle(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        // Create a cycle
        $detector->recordExecution('A', null);
        $detector->recordExecution('B', 'A');
        $detector->recordExecution('C', 'B');
        $detector->recordExecution('A', 'C');

        $cycles = $detector->getCycleReport();
        $this->assertNotEmpty($cycles);

        // Break the first cycle
        $detector->breakCycle($cycles[0]);

        // Cycle should be broken
        $cyclesAfter = $detector->getCycleReport();
        $this->assertEmpty($cyclesAfter);
    }

    public function testPolicyApplication(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        $policy = new \MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Policy(
            cooldownMs: 2000,
            dedupWindowMs: 3000,
            maxExecutions: 5,
            breakCycles: true,
            cycleBreakers: ['breaker_action']
        );

        $actionId = 'policy_action';
        $detector->applyPolicy($actionId, $policy);

        // Check that cooldown and dedup window were applied
        $this->assertTrue($detector->isInCooldown($actionId));
        $this->assertEquals(2000, $detector->getCooldown($actionId));
    }

    public function testGetStats(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        $detector->recordExecution('action1', null);
        $detector->recordExecution('action2', 'action1');

        $stats = $detector->getStats();
        $this->assertArrayHasKey('graph_nodes', $stats);
        $this->assertArrayHasKey('graph_edges', $stats);
        $this->assertArrayHasKey('actions_tracked', $stats);
        $this->assertArrayHasKey('cooldowns_active', $stats);
        $this->assertArrayHasKey('dedup_windows_active', $stats);
        $this->assertArrayHasKey('cycles_detected', $stats);
    }

    public function testReset(): void
    {
        $detector = new LoopDetector(
            cycleWindowMs: 30000,
            maxCycleLength: 10,
            defaultCooldownMs: 5000,
            dedupWindowMs: 5000
        );

        $detector->recordExecution('action1', null);
        $detector->applyCooldown('action1', 1000);

        $detector->reset();

        $this->assertFalse($detector->isInCooldown('action1'));
        $this->assertEquals(0, $detector->getStats()['actions_tracked']);
    }
}
