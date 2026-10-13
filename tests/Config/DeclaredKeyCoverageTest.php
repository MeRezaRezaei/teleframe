<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The audit must not rot: every key the package declares is classified, and
 * every key the audit calls dishonest SAYS SO in its own comment.
 *
 * This is the guard on the REGISTRY. DeadKeyRegressionTest is the guard on the
 * CLASSIFICATION. Between them a config key cannot join the dead-config class
 * quietly, and a fixed one cannot stay labelled dead.
 */
final class DeclaredKeyCoverageTest extends TestCase
{
    /**
     * A key on disk with no classification is a key nobody looked at — which
     * is exactly how TELEFRAME_STORE_BACKUP_QUEUE, user_lookup=field and the
     * schedules family all got in. Adding a config key now requires adding it
     * to the registry, in the same change.
     */
    public function test_every_declared_key_is_classified(): void
    {
        $classified = ConfigKeyRegistry::classification();
        $rows = ConfigKeyRegistry::ROW_CONTAINERS;

        $unclassified = array_values(array_diff(
            ConfigKeyRegistry::declaredKeys(),
            array_keys($classified),
            $rows,
        ));

        self::assertSame(
            [],
            $unclassified,
            "config key(s) declared but never classified by the dead-config audit:\n  "
            .implode("\n  ", $unclassified)
            ."\n\nAdd each to ConfigKeyRegistry::READ / DEAD / READ_BUT_IGNORED /"
            ."\nREAD_CONDITIONAL, with the file:line that reads it (or the proof"
            ."\nthat nothing does). An unclassified key is how this defect class grows."
        );
    }

    /**
     * A classification may not outlive the key. If a key is deleted from a
     * config file, the registry must drop it too.
     */
    public function test_no_classified_key_has_vanished_from_the_config_files(): void
    {
        $vanished = array_values(array_diff(
            array_keys(ConfigKeyRegistry::classification()),
            ConfigKeyRegistry::declaredKeys(),
        ));

        self::assertSame(
            [],
            $vanished,
            "key(s) still classified but no longer declared in any config file:\n  "
            .implode("\n  ", $vanished)
            ."\n\nRemove them from ConfigKeyRegistry in the same change that removes the key."
        );
    }

    /**
     * The honesty half: a key the audit calls DEAD or READ-BUT-IGNORED must
     * carry the marker in the config file itself. A key whose comment still
     * reads like a working knob is the promise the ledger says the code does
     * not keep — and no reader will ever catch it, because there is no reader.
     */
    #[DataProvider('dishonestKeys')]
    public function test_a_dishonest_key_declares_that_it_is(string $key): void
    {
        [$file, $leaf] = $this->locate($key);

        $comment = ConfigKeyRegistry::commentAbove($file, "'".$leaf."' =>");

        self::assertNotSame('', $comment, "no comment block found above {$file}: '{$leaf}'");

        $marked = ConfigKeyRegistry::marksAsDishonest($comment);

        self::assertTrue(
            $marked,
            "{$file}: the comment above '{$leaf}' does not say the key is DEAD, INERT,"
            ."\nREAD-BUT-IGNORED or conditionally read. A reader will never catch this"
            ."\nclass, so the comment is the only place the truth can live. Either wire"
            ."\nthe key, or state plainly that nothing reads it."
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dishonestKeys(): iterable
    {
        foreach (ConfigKeyRegistry::dishonest() as $key) {
            yield $key => [$key];
        }
    }

    /**
     * The registry is a table, so a duplicate entry is a copy/paste accident
     * that would let one key be counted in two classes at once.
     */
    public function test_no_key_is_classified_twice(): void
    {
        $all = array_merge(
            ConfigKeyRegistry::READ,
            ConfigKeyRegistry::DEAD,
            ConfigKeyRegistry::READ_BUT_IGNORED,
            ConfigKeyRegistry::READ_CONDITIONAL,
        );

        $duplicates = array_values(array_unique(array_diff_assoc($all, array_unique($all))));

        self::assertSame([], $duplicates, 'key(s) classified in more than one class: '.implode(', ', $duplicates));
    }

    /**
     * The counts are asserted so a mass deletion from the registry cannot pass
     * as "the audit shrank to fit". 2026-09-27: 148 declared keys =
     * 131 READ + 11 DEAD + 1 READ-BUT-IGNORED + 5 READ_CONDITIONAL.
     *
     * 2026-09-27 (follow-up pass): the eight dead/ignored keys were wired and
     * one was added, so 149 declared keys = 139 READ + 5 DEAD +
     * 0 READ-BUT-IGNORED + 5 READ_CONDITIONAL. The five that stay DEAD are the
     * Redis wire keys, a deliberate ruling recorded in ConfigKeyRegistry::DEAD.
     *
     * 2026-09-27 (third pass, 4190089a): two keys added for the MTProto
     * user-scope notify transport, so 151 declared keys = 141 READ + 5 DEAD +
     * 0 READ-BUT-IGNORED + 5 READ_CONDITIONAL. Both new keys are READ. The
     * count moved because the coverage test caught them UNCLASSIFIED first,
     * which is the mechanism working rather than a chore: a key added without
     * landing in the registry fails here instead of becoming the next
     * declared-but-read-by-nothing row.
     *
     * 2026-09-27 (fourth pass, f5a1db08 follow-up): one key added for the
     * opt-in backup schedule, so 162 declared keys = 152 READ + 5 DEAD +
     * 0 READ-BUT-IGNORED + 5 READ_CONDITIONAL. The new key is READ: the
     * provider's runningInConsole() block reads it to decide whether to
     * register the schedule.
     *
     * 2026-09-30 (fifth pass, Cycle 38 — Goal D): six keys added for the S3
     * durable-vault tier, so 168 declared keys = 158 READ + 5 DEAD +
     * 0 READ-BUT-IGNORED + 5 READ_CONDITIONAL. All six are READ and all six
     * share one reader, the S3GatewayContract binding in
     * TeleframeServiceProvider (see the block comment in ConfigKeyRegistry::READ).
     * The declared count moved 162 -> 168 because the S3 block was added to
     * the config file earlier today; the coverage test caught all six
     * UNCLASSIFIED first, which is the mechanism doing its job.
     *
     * 2026-10-12 (generation retention): one key added,
     * teleframe.backup.keep_generations, so 169 declared keys = 159 READ +
     * 5 DEAD + 0 READ-BUT-IGNORED + 5 READ_CONDITIONAL. It is READ by
     * BackupCommand::keepGenerations() — the width of the prune keep-set.
     */
    public function test_the_registry_still_covers_the_whole_declared_surface(): void
    {
        self::assertCount(169, ConfigKeyRegistry::declaredKeys());
        self::assertCount(159, ConfigKeyRegistry::READ);
        self::assertCount(5, ConfigKeyRegistry::DEAD);
        self::assertCount(0, ConfigKeyRegistry::READ_BUT_IGNORED);
        self::assertCount(5, ConfigKeyRegistry::READ_CONDITIONAL);
    }

    /**
     * @return array{string, string} config file, leaf key
     */
    private function locate(string $key): array
    {
        $pos = strrpos($key, '.');
        $leaf = substr($key, (int) $pos + 1);

        // Longest matching merge prefix wins, so a nested key resolves to the
        // file that actually declares it (teleframe.store.backup.* lands in
        // store-backup.php, not in the top-level teleframe.php).
        $best = null;
        foreach (ConfigKeyRegistry::FILES as $prefix => $file) {
            if ($key === $prefix || str_starts_with($key, $prefix.'.')) {
                if ($best === null || strlen($prefix) > strlen($best[0])) {
                    $best = [$prefix, $file];
                }
            }
        }

        self::assertNotNull($best, "no config file owns the key {$key}");

        return [$best[1], $leaf];
    }
}
