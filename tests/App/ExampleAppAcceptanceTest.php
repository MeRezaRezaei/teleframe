<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The example app IS the acceptance test for the three reachability breaks.
 *
 * examples/ai-read-and-reply.php is the owner's sentence runnable:
 * "connect my ai to read my telegram messages and answer them … just give me
 * a facade in codes". It declares a declarative app through the facade, and
 * then proves the three things that used to be broken:
 *
 *   BREAK 1 — the trigger is on the shared hot table after ONE define()
 *             call, with no install() by hand (the matcher finds it);
 *   BREAK 2 — it matches the REAL constructor name `updateNewMessage`, not
 *             just the `*` catch-all, and the whole read+answer runs on it;
 *   BREAK 3 — the app READS and ANSWERS through the facade surface.
 *
 * If a docblock lies, a method is renamed, or the boot install is dropped,
 * this test fails — which is the point of shipping the example at all.
 */
final class ExampleAppAcceptanceTest extends TestCase
{
    public function test_the_ai_read_and_reply_example_runs_offline_and_exits_zero(): void
    {
        $script = dirname(__DIR__, 2).'/examples/ai-read-and-reply.php';
        self::assertFileExists($script, 'the owner asked for a facade AND a working example');

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(
            [PHP_BINARY, $script, '--offline'],
            $descriptors,
            $pipes,
            dirname(__DIR__, 2),
            // A deliberately empty environment: the example must not need,
        );

        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        self::assertSame(0, $exit, "the example failed (exit {$exit}):\n".$stdout."\n".$stderr);

        // BREAK 1: the trigger is live after declare() alone.
        self::assertStringContainsString('matcher sees updateNewMessage: yes', $stdout);

        // BREAK 3: it read and it answered.
        self::assertStringContainsString('messages read: 2', $stdout);
        self::assertStringContainsString('replies sent: 1', $stdout);
        self::assertStringContainsString('offline acceptance: PASSED', $stdout);

        // Credentials ruling: an offline run touches no session, and the
        // default path prints no message body.
        self::assertStringNotContainsString('you said:', $stdout);
    }

    /** The example declares a scheduled call, so the schedule seam is shown. */
    public function test_the_example_declares_a_scheduled_call(): void
    {
        $script = dirname(__DIR__, 2).'/examples/ai-read-and-reply.php';

        $output = (string) shell_exec(
            escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' --offline 2>&1',
        );

        self::assertStringContainsString('app-call-ai-assistant-status-ping-', $output);
    }
}
