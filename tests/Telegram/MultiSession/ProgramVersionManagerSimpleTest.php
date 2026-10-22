<?php

declare(strict_types=1);

namespace MeRezaRezaei\Telegram\MultiSession;

use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use MeRezaRezaei\Telegram\MultiSession\StateMachine\StateMachine;
use PHPUnit\Framework\TestCase;

class ProgramVersionManagerSimpleTest extends TestCase
{
    private const PROGRAM_ID = 'merezarezaei_teleframe_telegram_multisession_multisessionprogram';

    public function test_register_and_get_program(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $this->assertNotNull($manager->getProgram(self::PROGRAM_ID));
        $state = $manager->getProgramState(self::PROGRAM_ID);
        $this->assertNotNull($state);
        $this->assertEquals('1.0.0', $state['version']);
    }

    public function test_get_all_program(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $programs = $manager->getAllPrograms();
        $this->assertContains(self::PROGRAM_ID, $programs);
    }

    public function test_add_migration(): void
    {
        $manager = new ProgramVersionManager;

        $manager->addMigration('test_program', '1.0.0', '1.1.0', function (array $state): array {
            $state['migrated'] = true;

            return $state;
        });

        $migrations = $manager->getMigrations('test_program');
        $this->assertArrayHasKey('1.0.0->1.1.0', $migrations);
    }

    public function test_upgrade_same_version(): void
    {
        $manager = new ProgramVersionManager;
        $program = new MultiSessionProgram;

        $manager->register($program);

        $result = $manager->upgrade(self::PROGRAM_ID, '1.0.0');

        $this->assertTrue($result->success);
        $this->assertEquals('Already at target version', $result->message);
    }

    public function test_multi_session_program_state_schema(): void
    {
        $program = new MultiSessionProgram;

        $schema = $program->getStateSchema();
        $this->assertEquals('1', $schema['version']);
        $this->assertArrayHasKey('components', $schema);
        $this->assertArrayHasKey('binder_registry', $schema['components']);
        $this->assertArrayHasKey('loop_detector', $schema['components']);
        $this->assertArrayHasKey('state_machine', $schema['components']);
        $this->assertArrayHasKey('session_router', $schema['components']);
        $this->assertArrayHasKey('business_connection_handler', $schema['components']);
        $this->assertArrayHasKey('channel_event_dispatcher', $schema['components']);
    }

    public function test_multi_session_program_validate_state(): void
    {
        $program = new MultiSessionProgram;

        // A new program has valid state structure (empty sessions array is valid)
        $this->assertTrue($program->validateState());
    }

    public function test_multi_session_program_getters(): void
    {
        $program = new MultiSessionProgram;

        $this->assertInstanceOf(BinderRegistry::class, $program->getBinderRegistry());
        $this->assertInstanceOf(LoopDetector::class, $program->getLoopDetector());
        $this->assertInstanceOf(StateMachine::class, $program->getStateMachine());
        $this->assertInstanceOf(SessionRouter::class, $program->getSessionRouter());
        $this->assertInstanceOf(BusinessConnectionHandler::class, $program->getBusinessConnectionHandler());
        $this->assertInstanceOf(ChannelEventDispatcher::class, $program->getChannelEventDispatcher());
    }

    public function test_multi_session_program_migrate_from(): void
    {
        $program = new MultiSessionProgram;

        // Should not throw
        $program->migrateFrom('0.9.0');
        $program->migrateFrom('1.0.0');

        $this->assertTrue(true);
    }

    public function test_multi_session_program_rollback_to(): void
    {
        $program = new MultiSessionProgram;

        // Should not throw
        $program->rollbackTo('1.0.0');

        $this->assertTrue(true);
    }
}
