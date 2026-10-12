<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * SchedulePublisher::describe() — the text an operator reads FIRST when a
 * scheduled job is not firing, or when they want to turn one on. Two claims
 * were false in it, in the class whose entire job is to be honest about state.
 *
 * 1. THE NULL TEXT NAMED CONFIG KEYS NOTHING READS. It said "Set
 *    teleframe.tstore.schedules=temporal (Temporal at
 *    teleframe.tstore.schedules_dsn…)". Both keys exist in config and NEITHER
 *    was read by any code — the tstore seam was hardcoded to
 *    NullSchedulePublisher itself, so following the instruction to the letter
 *    changed nothing. (Both providers were wired in ab1961bc; the assertion
 *    below still holds, for the different and sharper reason recorded at it.)
 *    test_every_key_the_null_text_names_is_really_read() below turns "I grepped
 *    and it looked fine" into a machine-checked invariant: it extracts each
 *    `teleframe.*` token out of the text and fails unless a provider in
 *    src/Laravel really reads it.
 *
 * 2. THE TEMPORAL TEXT CLAIMED AN ARMED STATE BEFORE ANYTHING WAS ARMED, AND
 *    SAID "tstore" ON THE APP AND BACKUP SEAMS. "tstore maintenance schedules
 *    are enabled" was printed unconditionally, before any install(), by the
 *    class bound at all three seams. On the app driver "tstore" is simply the
 *    wrong module.
 *
 * tests/Laravel/BackupSchedulePublisherTest.php:126 asserts
 * `assertStringNotContainsString('skipped', $publisher->describe())`. That
 * assertion passed against the old "…are enabled" string, so it did not cover
 * the case it names; what it SHOULD assert is spelled out in
 * test_describe_never_claims_schedules_are_enabled_before_install() below.
 */
final class SchedulePublisherDescriptionTest extends TestCase
{
    public function test_the_null_text_says_nothing_is_scheduled(): void
    {
        $text = (new NullSchedulePublisher)->describe();

        self::assertStringContainsString('Schedule driver: null', $text);
        self::assertStringContainsString('Nothing is scheduled', $text);
    }

    public function test_the_null_text_names_every_seam_that_can_now_be_armed(): void
    {
        $text = (new NullSchedulePublisher)->describe();

        // The history, because the reason for this text has moved twice.
        //
        // 1. It once said "Set teleframe.tstore.schedules=temporal" while
        //    NOTHING read that key. Both keys existed in config and neither
        //    was read by any code, so following the instruction to the letter
        //    changed nothing — the dead-config class at its worst, printed by
        //    the very class that means "off".
        // 2. Cycle 19 removed the mention instead of wiring the key, which
        //    was the right call then: not naming an unusable key beats
        //    naming one. ab1961bc wired both drivers.
        // 3. But wired is not ARMED. Nothing called install() on the tstore
        //    or notify seam, so the invariant could not be "unwired" — it
        //    was "no armed seam behind the key", and these two were still
        //    rightly unnamed.
        //
        // NOW THERE IS AN ARMING PATH on both (`teleframe:tstore schedules
        // --install`, `teleframe:notify schedules --install`), so both keys
        // belong in the text. What must hold, and is checked by the machine
        // test below, is that every key named here has a real reader.
        self::assertStringContainsString('teleframe.tstore.schedules=temporal', $text);
        self::assertStringContainsString('teleframe.notify.schedules=temporal', $text);

        // And it names the two keys that were always honest, so the
        // instruction is followable.
        self::assertStringContainsString('teleframe.app.schedules=temporal', $text);
        self::assertStringContainsString('teleframe.store.backup.schedule.publisher=temporal', $text);
        self::assertStringContainsString('composer require temporal/sdk', $text,
            'the optional runtime dependency must still be named, or the instruction is incomplete');

        // The sentence that keeps the text and the code in step: a driver
        // alone arms nothing, and the text now says which --install to run.
        self::assertStringContainsString('install() is what creates or refreshes', $text);
    }

    /**
     * The OTHER half of the arming story, and the reason the text above is
     * allowed to name a key at all: an arming point must EXIST for the seam
     * it names. These are the exact commands the text tells the operator to
     * run, asserted as literal strings in the command signatures, so a
     * renamed option or a reverted --install cannot leave the description
     * pointing at a command that no longer arms anything.
     */
    public function test_every_arming_command_the_null_text_names_really_exists(): void
    {
        $text = (new NullSchedulePublisher)->describe();

        foreach ([
            'teleframe:app calls --install',
            'teleframe:tstore schedule',
            'teleframe:tstore schedules --install',
            'teleframe:notify schedules --install',
        ] as $command) {
            self::assertStringContainsString($command, $text);
        }

        $signatures = (string) file_get_contents(
            dirname(__DIR__, 2).'/src/Laravel/Console/TstoreCommand.php',
        );
        self::assertStringContainsString('--install', $signatures,
            'the tstore command no longer offers --install, so the null text names a command that arms nothing');

        $app = (string) file_get_contents(dirname(__DIR__, 2).'/src/Laravel/Console/AppCommand.php');
        self::assertStringContainsString('--install', $app);

        $notify = (string) file_get_contents(dirname(__DIR__, 2).'/src/Teleframe/Notify/NotifyCommand.php');
        self::assertStringContainsString('--install', $notify);
    }

    public function test_every_key_the_null_text_names_is_really_read(): void
    {
        $text = (new NullSchedulePublisher)->describe();
        $keys = self::configKeysIn($text);

        self::assertNotEmpty($keys, 'the text must name at least one config key, or it is not an instruction');

        $read = self::keysReadByProviders();

        foreach ($keys as $key) {
            self::assertContains(
                $key,
                $read,
                "describe() names {$key}, which NO provider in src/Laravel reads. "
                .'A config key that is declared, documented and read by nothing is a promise the code '
                .'does not keep, and this text is the one place it reaches the operator terminal.',
            );
        }
    }

    public function test_describe_never_claims_schedules_are_enabled_before_install(): void
    {
        // This is what tests/Laravel/BackupSchedulePublisherTest.php:126 SHOULD
        // assert. Its `assertStringNotContainsString('skipped', …)` passed
        // against the old "…are enabled" string, so it could not fail for the
        // reason it names.
        $fresh = new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient());

        self::assertStringNotContainsString('enabled', strtolower($fresh->describe()));
        self::assertStringContainsString('Nothing is armed', $fresh->describe());
        self::assertStringContainsString('install() has not run', $fresh->describe());
    }

    public function test_the_temporal_text_never_mentions_one_module_as_if_it_were_all_of_them(): void
    {
        // This class is bound at the app seam, the backup seam and the tstore
        // seam. "tstore maintenance schedules" was the wrong module name on two
        // of the three.
        foreach ([
            new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient()),
            new TemporalSchedulePublisher('127.0.0.1:7233', $this->oneShotClient()),
            new TemporalSchedulePublisher('127.0.0.1:7233'),
        ] as $publisher) {
            self::assertStringNotContainsString('tstore', $publisher->describe());
            self::assertStringContainsString('Schedule driver: temporal', $publisher->describe());
            self::assertStringContainsString('127.0.0.1:7233', $publisher->describe());
        }
    }

    public function test_describe_reports_only_what_this_process_actually_armed(): void
    {
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', $this->recurringClient());

        $publisher->install([
            'backup-dump' => ['activity' => 'teleframe:tstore backup', 'interval_seconds' => 900],
            'app-call-echo-bot-health-abc123' => [
                'activity' => 'telegram.app.run-call echo-bot health',
                'interval_seconds' => 60,
            ],
        ]);

        $text = $publisher->describe();

        self::assertStringContainsString('2 schedule(s) armed', $text);
        self::assertStringContainsString('backup-dump', $text);
        self::assertStringContainsString('app-call-echo-bot-health-abc123', $text);
        self::assertStringNotContainsString('Nothing is armed', $text);
        self::assertStringContainsString('not a query of the Temporal server', $text,
            'the text must not imply it read live server state');
    }

    public function test_a_one_shot_client_still_says_so_and_still_reports_nothing_armed(): void
    {
        $text = (new TemporalSchedulePublisher('127.0.0.1:7233', $this->oneShotClient()))->describe();

        self::assertStringContainsString('one-shot', $text);
        self::assertStringContainsString('Nothing is armed', $text);
    }

    // ------------------------------------------------------------------ readers --

    /**
     * Every `teleframe.*` config key literal that appears in a description.
     *
     * @return list<string>
     */
    private static function configKeysIn(string $text): array
    {
        preg_match_all('/teleframe\.[a-z0-9_.]+/', $text, $matches);

        $keys = [];
        foreach ($matches[0] as $key) {
            $key = rtrim($key, '.');

            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * The config keys a provider in src/Laravel actually READS — the literal
     * passed to a `get(` call, in any file EXCEPT src/Laravel/config/ (where
     * every key is declared, and where a key can also appear inside the
     * comment block documenting that it is dead).
     *
     * @return list<string>
     */
    private static function keysReadByProviders(): array
    {
        $root = dirname(__DIR__, 2).'/src/Laravel';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $read = [];
        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains(str_replace('\\', '/', $path), '/src/Laravel/config/')) {
                continue;
            }

            $source = (string) file_get_contents($path);
            preg_match_all('/get\(\s*[\'"]([a-z0-9_.]+)[\'"]/', $source, $matches);

            foreach ($matches[1] as $key) {
                $read[$key] = true;
            }
        }

        $keys = array_keys($read);
        sort($keys);

        return $keys;
    }

    // ------------------------------------------------------------------ doubles --

    private function recurringClient(): RecurringScheduleClient
    {
        return new class implements RecurringScheduleClient
        {
            /** @var list<string> */
            public array $armed = [];

            public function createOrUpdateSchedule(
                string $scheduleId,
                string $activity,
                int $intervalSeconds,
                string $taskQueue,
                array $args = [],
            ): string {
                $this->armed[] = $scheduleId;

                return $scheduleId;
            }

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-should-not-be-used';
            }
        };
    }

    private function oneShotClient(): TemporalScheduleClient
    {
        return new class implements TemporalScheduleClient
        {
            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-1';
            }
        };
    }
}
