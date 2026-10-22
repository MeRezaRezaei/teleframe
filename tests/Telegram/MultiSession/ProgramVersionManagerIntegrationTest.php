<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession;

use MeRezaRezaei\Telegram\MultiSession\QueryEngine\Policy;
use PHPUnit\Framework\TestCase;

class ProgramVersionManagerIntegrationTest extends TestCase
{
    public function test_upgrade_and_rollback(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $programId = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

        // Add migration from 1.0.0 to 1.1.0
        $manager->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['migrated_to_1_1'] = true;
            $state['version'] = '1.1.0';

            return $state;
        });

        // Add rollback migration from 1.1.0 to 1.0.0
        $manager->addRollbackMigration($programId, '1.1.0', '1.0.0', function (array $state): array {
            unset($state['migrated_to_1_1']);
            $state['version'] = '1.0.0';

            return $state;
        });

        // Test upgrade
        $result = $manager->upgrade($programId, '1.1.0');
        $this->assertTrue($result->success);
        $this->assertEquals('1.1.0', $program->getVersion());
        $this->assertTrue($program->getState()['migrated_to_1_1'] ?? false);

        // Test rollback
        $result = $manager->rollback($programId, '1.0.0');
        $this->assertTrue($result->success);
        $this->assertEquals('1.0.0', $program->getVersion());
        $this->assertFalse($program->getState()['migrated_to_1_1'] ?? false);
    }

    public function test_upgrade_with_validation_failure(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $programId = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

        // Add migration that makes state invalid
        $manager->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['invalid'] = true;
            // Remove required session router data to make validation fail
            unset($state['session_router']);

            return $state;
        });

        $result = $manager->upgrade($programId, '1.1.0');
        $this->assertFalse($result->success);
        $this->assertEquals('1.0.0', $program->getVersion());
        $this->assertArrayHasKey('session_router', $program->getState());
    }

    public function test_rollback_without_rollback_migration(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $programId = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

        // Add migration without rollback migration
        $manager->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['migrated_to_1_1'] = true;
            $state['version'] = '1.1.0';

            return $state;
        });

        // Upgrade first
        $manager->upgrade($programId, '1.1.0');

        // Try to rollback - should fail because no rollback migration
        $result = $manager->rollback($programId, '1.0.0');
        $this->assertFalse($result->success);
        $this->assertEquals('1.1.0', $program->getVersion());
    }

    public function test_multi_step_upgrade(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $programId = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

        // Add multi-step migrations
        $manager->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['step1'] = true;
            $state['version'] = '1.1.0';

            return $state;
        });

        $manager->addMigration($programId, '1.1.0', '1.2.0', function (array $state): array {
            $state['step2'] = true;
            $state['version'] = '1.2.0';

            return $state;
        });

        $manager->addMigration($programId, '1.2.0', '2.0.0', function (array $state): array {
            $state['step3'] = true;
            $state['version'] = '2.0.0';

            return $state;
        });

        // Test multi-step upgrade
        $result = $manager->upgrade($programId, '2.0.0');
        $this->assertTrue($result->success);
        $this->assertEquals('2.0.0', $program->getVersion());
        $this->assertTrue($program->getState()['step1'] ?? false);
        $this->assertTrue($program->getState()['step2'] ?? false);
        $this->assertTrue($program->getState()['step3'] ?? false);
    }

    public function test_rollback_multi_step(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $programId = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

        // Add migrations with rollback migrations
        $manager->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['step1'] = true;
            $state['version'] = '1.1.0';

            return $state;
        });
        $manager->addRollbackMigration($programId, '1.1.0', '1.0.0', function (array $state): array {
            unset($state['step1']);
            $state['version'] = '1.0.0';

            return $state;
        });

        $manager->addMigration($programId, '1.1.0', '1.2.0', function (array $state): array {
            $state['step2'] = true;
            $state['version'] = '1.2.0';

            return $state;
        });
        $manager->addRollbackMigration($programId, '1.2.0', '1.1.0', function (array $state): array {
            unset($state['step2']);
            $state['version'] = '1.1.0';

            return $state;
        });

        // Upgrade to 1.2.0
        $manager->upgrade($programId, '1.2.0');
        $this->assertEquals('1.2.0', $program->getVersion());

        // Rollback to 1.0.0
        $result = $manager->rollback($programId, '1.0.0');
        $this->assertTrue($result->success);
        $this->assertEquals('1.0.0', $program->getVersion());
        $this->assertFalse($program->getState()['step1'] ?? false);
        $this->assertFalse($program->getState()['step2'] ?? false);
    }

    public function test_policy_application_during_upgrade(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);
        $programId = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

        $loopDetector = $program->getLoopDetector();
        $policy = new Policy(
            cooldownMs: 1000,
            dedupWindowMs: 5000,
            maxExecutions: 5,
            breakCycles: true,
            cycleBreakers: ['breaker_action']
        );

        $loopDetector->applyPolicy('test_action', $policy);

        // Upgrade should preserve policy
        $manager->addMigration($programId, '1.0.0', '1.1.0', function (array $state): array {
            $state['version'] = '1.1.0';

            return $state;
        });

        $result = $manager->upgrade($programId, '1.1.0');
        $this->assertTrue($result->success);

        // Policy should still be applied
        $this->assertTrue($loopDetector->isInCooldown('test_action'));
    }
}
