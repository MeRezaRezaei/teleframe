<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * THE ROT TEST. A known-dead key that comes back to life must FAIL here.
 *
 * Wiring one of these keys is the right thing to do — it is what the ledger
 * demands ("to be either wired or removed, not silently left"). But it is only
 * right if the config file stops calling it dead, because the comment is what
 * the next auditor reads. So the invariant is not "these keys stay dead". It
 * is: THESE KEYS AND THEIR LABELS MUST AGREE.
 *
 * A reader appearing next to a comment that still says DEAD is the exact
 * moment the audit starts lying, and that is what this test refuses.
 */
final class DeadKeyRegressionTest extends TestCase
{
    /**
     * Today every DEAD key has no reader. If one gains a reader, the wiring
     * is welcome — but the config comment must be corrected in the same change,
     * or this fails and says so.
     */
    #[DataProvider('deadKeys')]
    public function test_a_dead_key_is_either_still_unread_or_no_longer_called_dead(string $key): void
    {
        $readers = $this->readersOf($key);
        $comment = $this->commentFor($key);

        $stillLabelledDead = ConfigKeyRegistry::marksAsDishonest($comment);

        if ($readers === []) {
            self::assertTrue(
                $stillLabelledDead,
                "{$key} has no reader, so its comment must say so. It does not — a key"
                ."\nnothing reads, described as a working knob, is the dead-config class."
            );

            return;
        }

        self::assertFalse(
            $stillLabelledDead,
            "{$key} now HAS a reader but its config comment still calls it DEAD/INERT:"
            ."\n  ".implode("\n  ", $readers)
            ."\n\nWiring it is correct — now correct the comment (move the key to"
            ."\nConfigKeyRegistry::READ and drop the DEAD/INERT marker) in the same change,"
            ."\nso the next reader of this file is not misled."
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function deadKeys(): iterable
    {
        foreach (ConfigKeyRegistry::DEAD as $key) {
            yield $key => [$key];
        }
    }

    /**
     * The documented ledger ruling for TELEFRAME_STORE_BACKUP_QUEUE, pinned so
     * it cannot drift back to "deliberately NOT wired".
     *
     * docs/verbatim-goals.md records this key as unwired dead config. That
     * record is STALE: StoreBackupServiceProvider::registerUploadQueue() reads
     * it at :331 and rebinds teleclient.tstore.queue at :337, and because
     * Container::bind() is last-write-wins and composer.json lists
     * StoreBackupServiceProvider (index 2) after TeleframeServiceProvider
     * (index 0), the rebind WINS. The key is wired.
     */
    public function test_the_backup_queue_key_is_wired_not_dead_config(): void
    {
        $readers = $this->readersOf('teleframe.store.backup.queue');

        self::assertNotSame(
            [],
            $readers,
            'teleframe.store.backup.queue has NO reader. The ledger records it as dead'
            ."\nconfig pending a wire-or-remove decision; if that regression is real, wire"
            ."\nor remove it deliberately and update this test — do not leave it dead."
        );

        // The reader must sit INSIDE registerUploadQueue(). This used to be
        // pinned to a hardcoded ':331', which is the third time an unrelated
        // edit to this provider would have broken a test about a DIFFERENT
        // key's wiring — a line number is not an invariant, the method is.
        self::assertNotSame(
            [],
            array_values(array_intersect($readers, self::readersInside('StoreBackupServiceProvider', 'registerUploadQueue'))),
            'teleframe.store.backup.queue is read somewhere other than'
            ."\nStoreBackupServiceProvider::registerUploadQueue(). Re-verify that the"
            ."\nbinding still wins by provider order before trusting the ledger ruling."
        );

        self::assertNotContains(
            'teleframe.store.backup.queue',
            ConfigKeyRegistry::DEAD,
            'teleframe.store.backup.queue is wired; it must not be classified DEAD.'
        );
    }

    /**
     * Reader sites that fall inside one named method of one provider file.
     *
     * @return list<string>
     */
    private static function readersInside(string $file, string $method): array
    {
        $path = ConfigKeyRegistry::repoRoot().'/src/Laravel/'.$file.'.php';
        $lines = file($path);
        if ($lines === false) {
            return [];
        }

        $start = null;
        $end = null;
        foreach ($lines as $i => $line) {
            if ($start === null && str_contains($line, 'function '.$method.'(')) {
                $start = $i;

                continue;
            }
            // The method body ends at the next line that closes it: the first
            // `    }` at the method's own indentation. String work, no regex.
            if ($start !== null
                && $i > $start
                && str_starts_with($line, '    }')) {
                $end = $i;

                break;
            }
        }

        if ($start === null || $end === null) {
            return [];
        }

        $sites = [];
        for ($i = $start; $i <= $end; $i++) {
            $sites[] = 'src/Laravel/'.$file.'.php:'.($i + 1);
        }

        return $sites;
    }

    /**
     * reader evidence for a dotted config key: the code lines that read it.
     *
     * Two read shapes count, because the tree uses both:
     *   (A) the full dotted literal inside a config accessor, and
     *   (B) the parent block read in a file that then indexes the leaf
     *       (`$cfg = $config->get('teleframe.x')` + `$cfg['leaf']`).
     * Comment lines never count — a mention is not a read, which is how
     * NullSchedulePublisher's error message naming teleframe.tstore.schedules
     * was mistaken for a reader until the accessor was checked.
     *
     * @return list<string>
     */
    private function readersOf(string $key): array
    {
        $pos = strrpos($key, '.');
        $leaf = substr($key, (int) $pos + 1);
        $parent = substr($key, 0, (int) $pos);

        $hits = [];

        foreach ($this->sourceFiles() as $path) {
            $lines = file($path);
            if ($lines === false) {
                continue;
            }

            $readsParent = false;
            foreach ($lines as $line) {
                if ($this->mentions($line, $parent)) {
                    $readsParent = true;
                    break;
                }
            }

            foreach ($lines as $i => $line) {
                $where = str_replace(ConfigKeyRegistry::repoRoot().'/', '', $path).':'.($i + 1);

                if ($this->isComment($line)) {
                    continue;
                }

                if ($this->mentions($line, $key) && $this->isAccessor($line)) {
                    $hits[] = $where;

                    continue;
                }

                if ($readsParent
                    && (str_contains($line, "['".$leaf."']") || str_contains($line, '["'.$leaf.'"]'))) {
                    $hits[] = $where;
                }
            }
        }

        return array_values(array_unique($hits));
    }

    private function commentFor(string $key): string
    {
        $leaf = substr($key, (int) strrpos($key, '.') + 1);

        // Longest matching merge prefix wins — a nested key lives in the file
        // that declares it, not in the top-level one.
        $best = null;
        foreach (ConfigKeyRegistry::FILES as $prefix => $file) {
            if (($key === $prefix || str_starts_with($key, $prefix.'.'))
                && ($best === null || strlen($prefix) > strlen($best[0]))) {
                $best = [$prefix, $file];
            }
        }

        if ($best === null) {
            return '';
        }

        return ConfigKeyRegistry::commentAbove($best[1], "'".$leaf."' =>");
    }

    private function mentions(string $line, string $key): bool
    {
        return str_contains($line, "'".$key."'") || str_contains($line, '"'.$key.'"');
    }

    private function isComment(string $line): bool
    {
        $t = ltrim($line);
        foreach (['*', '//', '#', '/*'] as $prefix) {
            if (str_starts_with($t, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAccessor(string $line): bool
    {
        foreach (['config(', '->get(', '->has(', '->string(', '->int(', '->bool(', '->array(', 'data_get(', 'configOrEnv('] as $needle) {
            if (str_contains($line, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every PHP file under src/ and bin/ — the whole surface that could hold
     * a reader. tests/ is excluded on purpose: a test that mentions a dead key
     * is not a reader, and this registry is full of such mentions.
     *
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [];

        foreach (['src', 'bin'] as $dir) {
            $base = ConfigKeyRegistry::repoRoot().'/'.$dir;
            if (! is_dir($base)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
            foreach ($it as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
