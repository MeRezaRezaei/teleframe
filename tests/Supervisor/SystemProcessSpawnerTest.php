<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Supervisor;

use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The spawn failure message is a LOG line.
 *
 * Supervisor::start() logs whatever this message says, on every failed start,
 * for the whole life of the restart ladder — so anything in it is written over
 * and over. A host-supervised spec may carry a credential in argv (that is
 * exactly what $spec->env exists to avoid), so the command line must not be
 * in the message. The name and the reason are the whole diagnostic.
 */
final class SystemProcessSpawnerTest extends TestCase
{
    public function test_a_failed_start_never_names_the_command_line(): void
    {
        $secret = 'bot-token-123456:AAHsuperSecretValue';
        $spawner = new SystemProcessSpawner;

        try {
            $spawner->spawn(new ProcessSpec(
                name: 'host-worker',
                command: ['/nonexistent/tf-supervised-binary', '--token', $secret],
                env: ['TELEFRAME_BOT_TOKEN' => $secret],
            ));
            self::fail('expected proc_open to fail for a nonexistent binary');
        } catch (RuntimeException $e) {
            $message = $e->getMessage();

            self::assertStringNotContainsString($secret, $message, 'a credential in argv must never be logged');
            self::assertStringNotContainsString('bot-token', $message);
            self::assertStringNotContainsString('/nonexistent', $message, 'the command line must not be in the message');
            self::assertStringNotContainsString('--token', $message);

            // The diagnostic that IS useful survives: which process, and why.
            self::assertStringContainsString('host-worker', $message);
            self::assertStringContainsString('proc_open failed', $message);
        }
    }

    public function test_the_spawner_is_inert_at_construction(): void
    {
        // The standalone smoke constructs every module; spawning must not
        // happen until spawn() is actually reached.
        $spawner = new SystemProcessSpawner;

        self::assertInstanceOf(SystemProcessSpawner::class, $spawner);
    }
}
