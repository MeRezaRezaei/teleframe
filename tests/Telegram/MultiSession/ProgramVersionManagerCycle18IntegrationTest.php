<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Deduplicator;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Telegram\MultiSession\BinderRegistry;
use MeRezaRezaei\Telegram\MultiSession\QueryEngine\Policy;
use MeRezaRezaei\Telegram\MultiSession\StateMachine\StateMachine;
use PHPUnit\Framework\TestCase;

class ProgramVersionManagerCycle18IntegrationTest extends TestCase
{
    private const PROGRAM_ID = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

    public function test_state_includes_cycle18_components(): void
    {
        $program = new MultiSessionProgram;

        $state = $program->getState();
        $this->assertArrayHasKey('deduplicator', $state);
        $this->assertArrayHasKey('query_engine', $state);
        $this->assertArrayHasKey('conversation_state_machine', $state);
        $this->assertArrayHasKey('state_machines', $state);

        $this->assertArrayHasKey('dedup_keys', $state['deduplicator']);
        $this->assertArrayHasKey('ttl', $state['deduplicator']);
        $this->assertArrayHasKey('registered_queries', $state['query_engine']);
        $this->assertArrayHasKey('loop_patterns', $state['query_engine']);
        $this->assertArrayHasKey('per_peer_states', $state['conversation_state_machine']);

        $this->assertTrue($program->validateState());

        $schema = $program->getStateSchema();
        $this->assertArrayHasKey('deduplicator', $schema['components']);
        $this->assertArrayHasKey('query_engine', $schema['components']);
        $this->assertArrayHasKey('conversation_state_machine', $schema['components']);
    }

    public function test_getters_for_cycle18_components(): void
    {
        $program = new MultiSessionProgram;

        $this->assertInstanceOf(Deduplicator::class, $program->getDeduplicator());
        $this->assertInstanceOf(BinderRegistry::class, $program->getQueryEngine());
        $this->assertInstanceOf(StateMachine::class, $program->getConversationStateMachine());
        $this->assertSame($program->getStateMachine(), $program->getConversationStateMachine());
    }

    public function test_deduplicator_state_backup_restore(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        // Populate deduplicator state
        $state = $program->getState();
        $state['deduplicator']['dedup_keys'] = ['account:1:update:42:hash'];
        $state['deduplicator']['ttl'] = 120;
        $program->setState($state);

        $this->assertSame(['account:1:update:42:hash'], $program->getState()['deduplicator']['dedup_keys']);

        // Backup
        $backup = $manager->getProgramState(self::PROGRAM_ID);
        $this->assertNotNull($backup);
        $this->assertSame(['account:1:update:42:hash'], $backup['state']['deduplicator']['dedup_keys']);
    }

    public function test_query_engine_state_backup_restore(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $state = $program->getState();
        $state['query_engine']['registered_queries'] = ['q1', 'q2'];
        $state['query_engine']['loop_patterns'] = [['a', 'b']];
        $program->setState($state);

        $backup = $manager->getProgramState(self::PROGRAM_ID);
        $this->assertSame(['q1', 'q2'], $backup['state']['query_engine']['registered_queries']);
        $this->assertSame([['a', 'b']], $backup['state']['query_engine']['loop_patterns']);
    }

    public function test_conversation_state_machine_per_peer_state(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $state = $program->getState();
        $state['conversation_state_machine']['per_peer_states'] = [
            'peer-1' => ['id' => 'awaiting_input', 'data' => ['name' => 'x']],
        ];
        $state['conversation_state_machine']['transitions'] = ['idle->awaiting_input'];
        $state['state_machines']['sessions'] = ['peer-1' => ['state' => 'awaiting_input']];
        $program->setState($state);

        $this->assertTrue($program->validateState());

        $backup = $manager->getProgramState(self::PROGRAM_ID);
        $this->assertArrayHasKey('peer-1', $backup['state']['conversation_state_machine']['per_peer_states']);
        $this->assertSame(['peer-1' => ['state' => 'awaiting_input']], $backup['state']['state_machines']['sessions']);
    }

    public function test_upgrade_preserves_cycle18_state(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        // Populate state
        $state = $program->getState();
        $state['deduplicator']['dedup_keys'] = ['k1'];
        $state['query_engine']['registered_queries'] = ['q1'];
        $state['conversation_state_machine']['per_peer_states'] = ['p1' => ['id' => 'idle']];
        $program->setState($state);

        $manager->addMigration(self::PROGRAM_ID, '1.0.0', '1.1.0', function (array $state): array {
            $state['migrated_to_1_1'] = true;
            $state['version'] = '1.1.0';
            $state['deduplicator']['dedup_keys'][] = 'k2';
            $state['query_engine']['registered_queries'][] = 'q2';
            $state['conversation_state_machine']['per_peer_states']['p2'] = ['id' => 'processing'];

            return $state;
        });

        $result = $manager->upgrade(self::PROGRAM_ID, '1.1.0');
        $this->assertTrue($result->success);
        $this->assertEquals('1.1.0', $program->getVersion());
        $this->assertTrue($program->getState()['migrated_to_1_1'] ?? false);

        // Original state preserved + migration additions
        $this->assertContains('k1', $program->getState()['deduplicator']['dedup_keys']);
        $this->assertContains('k2', $program->getState()['deduplicator']['dedup_keys']);
        $this->assertContains('q1', $program->getState()['query_engine']['registered_queries']);
        $this->assertContains('q2', $program->getState()['query_engine']['registered_queries']);
        $this->assertArrayHasKey('p1', $program->getState()['conversation_state_machine']['per_peer_states']);
        $this->assertArrayHasKey('p2', $program->getState()['conversation_state_machine']['per_peer_states']);
    }

    public function test_rollback_restores_cycle18_state(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $state = $program->getState();
        $state['deduplicator']['dedup_keys'] = ['orig'];
        $state['query_engine']['registered_queries'] = ['origq'];
        $program->setState($state);

        $manager->addMigration(self::PROGRAM_ID, '1.0.0', '1.1.0', function (array $state): array {
            $state['migrated_to_1_1'] = true;
            $state['version'] = '1.1.0';
            $state['deduplicator']['dedup_keys'] = ['changed'];
            $state['query_engine']['registered_queries'] = ['changedq'];

            return $state;
        });

        $manager->addRollbackMigration(self::PROGRAM_ID, '1.1.0', '1.0.0', function (array $state): array {
            unset($state['migrated_to_1_1']);
            $state['version'] = '1.0.0';
            $state['deduplicator']['dedup_keys'] = ['orig'];
            $state['query_engine']['registered_queries'] = ['origq'];

            return $state;
        });

        $manager->upgrade(self::PROGRAM_ID, '1.1.0');
        $this->assertEquals('1.1.0', $program->getVersion());

        $result = $manager->rollback(self::PROGRAM_ID, '1.0.0');
        $this->assertTrue($result->success);
        $this->assertEquals('1.0.0', $program->getVersion());
        $this->assertFalse($program->getState()['migrated_to_1_1'] ?? false);
        $this->assertSame(['orig'], $program->getState()['deduplicator']['dedup_keys']);
        $this->assertSame(['origq'], $program->getState()['query_engine']['registered_queries']);
    }

    public function test_upgrade_rollback_full_cycle18_stack(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $manager->registerCycle18Migrations(self::PROGRAM_ID);

        // Populate all components
        $state = $program->getState();
        $state['deduplicator']['dedup_keys'] = ['d1'];
        $state['deduplicator']['ttl'] = 90;
        $state['query_engine']['registered_queries'] = ['rq1'];
        $state['query_engine']['loop_patterns'] = [['x', 'y']];
        $state['conversation_state_machine']['per_peer_states'] = ['peer-a' => ['id' => 'processing']];
        $state['state_machines']['sessions'] = ['peer-a' => ['state' => 'processing']];
        $program->setState($state);

        // Upgrade
        $result = $manager->upgrade(self::PROGRAM_ID, '1.1.0');
        $this->assertTrue($result->success, $result->message);
        $this->assertEquals('1.1.0', $program->getVersion());
        $this->assertTrue($program->validateState());

        // Rollback
        $result = $manager->rollback(self::PROGRAM_ID, '1.0.0');
        $this->assertTrue($result->success, $result->message);
        $this->assertEquals('1.0.0', $program->getVersion());
        $this->assertTrue($program->validateState());
    }

    public function test_deduplicator_integration_with_state(): void
    {
        $program = new MultiSessionProgram;
        $dedup = $program->getDeduplicator();

        $this->assertSame(60, $dedup->getTtl());

        // Simulate dedup keys in state
        $state = $program->getState();
        $state['deduplicator']['dedup_keys'] = ['account:1:u:1'];
        $state['deduplicator']['ttl'] = $dedup->getTtl();
        $program->setState($state);

        $this->assertTrue($program->validateState());
        $this->assertSame(['account:1:u:1'], $program->getState()['deduplicator']['dedup_keys']);
    }

    public function test_loop_detector_policy_preserved(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $loopDetector = $program->getLoopDetector();
        $policy = new Policy(
            cooldownMs: 1000,
            dedupWindowMs: 5000,
            maxExecutions: 5,
            breakCycles: true,
            cycleBreakers: ['breaker_action']
        );
        $loopDetector->applyPolicy('test_action', $policy);

        $manager->addMigration(self::PROGRAM_ID, '1.0.0', '1.1.0', function (array $state): array {
            $state['version'] = '1.1.0';

            return $state;
        });

        $result = $manager->upgrade(self::PROGRAM_ID, '1.1.0');
        $this->assertTrue($result->success);
        $this->assertTrue($loopDetector->isInCooldown('test_action'));
    }

    public function test_register_cycle18_migrations_method(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $manager->registerCycle18Migrations(self::PROGRAM_ID);
        $migrations = $manager->getMigrations(self::PROGRAM_ID);
        $this->assertArrayHasKey('1.0.0->1.1.0', $migrations);

        $rollbacks = $manager->getRollbackMigrations(self::PROGRAM_ID);
        $this->assertArrayHasKey('1.1.0->1.0.0', $rollbacks);
    }

    public function test_program_register_migrations_helper(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $program->registerMigrations($manager);

        $migrations = $manager->getMigrations(self::PROGRAM_ID);
        $this->assertArrayHasKey('1.0.0->1.1.0', $migrations);
    }

    public function test_multi_step_upgrade_cycle18(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $manager->addMigration(self::PROGRAM_ID, '1.0.0', '1.1.0', function (array $state): array {
            $state['step1'] = true;
            $state['version'] = '1.1.0';
            $state['deduplicator']['dedup_keys'][] = 'step1';

            return $state;
        });

        $manager->addMigration(self::PROGRAM_ID, '1.1.0', '1.2.0', function (array $state): array {
            $state['step2'] = true;
            $state['version'] = '1.2.0';
            $state['query_engine']['registered_queries'][] = 'step2';

            return $state;
        });

        $result = $manager->upgrade(self::PROGRAM_ID, '1.2.0');
        $this->assertTrue($result->success);
        $this->assertEquals('1.2.0', $program->getVersion());
        $this->assertTrue($program->getState()['step1'] ?? false);
        $this->assertTrue($program->getState()['step2'] ?? false);
    }

    public function test_validation_failure_restores_state(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $state = $program->getState();
        $state['deduplicator']['dedup_keys'] = ['orig'];
        $program->setState($state);

        $manager->addMigration(self::PROGRAM_ID, '1.0.0', '1.1.0', function (array $state): array {
            // Make state invalid
            unset($state['session_router']);

            return $state;
        });

        $result = $manager->upgrade(self::PROGRAM_ID, '1.1.0');
        $this->assertFalse($result->success);
        $this->assertEquals('1.0.0', $program->getVersion());
        $this->assertArrayHasKey('session_router', $program->getState());
        $this->assertSame(['orig'], $program->getState()['deduplicator']['dedup_keys']);
    }
}
