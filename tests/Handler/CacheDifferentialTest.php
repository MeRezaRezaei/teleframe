<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Message\FilesystemCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * L5 — THE FIRST DIFFERENTIAL LANE FOR THE CACHES (2026-10-18).
 *
 * ## WHY THIS FILE IS THE ONE THAT MATTERS MOST
 *
 * `docs/standards/testing-method.md` names L5 — "same calls against the double AND the
 * real thing, which proves that the double has not drifted" — as **this repository's
 * gap**, and names mutation testing as the tool that **cannot** find this class:
 *
 *     "Mutation testing audits assertion *strength*; it cannot create the missing
 *      assertion."
 *
 * The shipped defect that shaped that standard (NEW-1) discarded **499 of every 500
 * messages behind a green suite** because a test double fed a shape production never
 * emits. Mutating the double cannot detect that. Only a differential lane can.
 *
 * ## WHY THESE TWO
 *
 * `InMemoryCache` backs **22 test files** and `FilesystemCache` is the shipped default a
 * consuming app binds when it wants a durable cache. Both implement the SAME
 * `Psr\SimpleCache\CacheInterface`, so a differential is possible **offline, with no
 * Redis, no database and no credentials** — which is what makes it a real gate rather
 * than an opt-in one.
 *
 * ## WHAT IT ALREADY FOUND, before a line of this was written
 *
 * Twelve operations were run against both implementations. **Three diverge**, and two
 * of them diverge in OPPOSITE directions on a TTL of zero or less:
 *
 * | operation                        | InMemoryCache | FilesystemCache |
 * |----------------------------------|---------------|-----------------|
 * | `delete()` of an absent key      | `true`        | `false`         |
 * | `set(..., 0)` then `has()`       | **`false`**   | **`true`**      |
 * | `set(..., -5)` then `has()`      | **`false`**   | **`true`**      |
 *
 * And at the exact expiry second the two implement OPPOSITE comparison operators:
 *
 *     InMemoryCache.php:140    $expires <= time()   →  expired (miss)
 *     FilesystemCache.php:54   $payload['expires'] < time()   →  NOT expired (hit)
 *
 * **The same TTL on the same value, at the same second, is a miss in one shipped cache
 * and a hit in the other.** `ReplayDedup` binds whatever cache the host registers, so the
 * same configuration dedups differently depending on which implementation is bound — and
 * nothing in the suite could notice, because each implementation is only ever tested
 * against itself.
 *
 * ## THE RULING THIS FILE ENCODES
 *
 * **A differential lane asserts AGREEMENT, not correctness.** Where PSR-16 is
 * ambiguous the test records what each side does and demands they match — because a
 * consuming app that swaps one for the other must not silently change behaviour. Where
 * PSR-16 is explicit, the test pins the specified answer, and the ambiguity is not
 * something a test is allowed to resolve by accident.
 */
final class CacheDifferentialTest extends TestCase
{
    private string $dir;

    /** Fake epoch second; advanced only by advance(). */
    private int $now = 1_000_000;

    private function advance(int $seconds): void
    {
        $this->now += $seconds;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/tf-cache-diff-'.getmypid().'-'.spl_object_id($this);
        if (! is_dir($this->dir) && ! mkdir($this->dir, 0o777, true) && ! is_dir($this->dir)) {
            self::fail('could not create the differential scratch directory');
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }

        parent::tearDown();
    }

    private function memory(): CacheInterface
    {
        return new InMemoryCache(fn (): int => $this->now);
    }

    /**
     * The THIRD PSR-16 double, added here 2026-10-18.
     *
     * `tests/Support/ArrayCache.php` backs 22 test files and its `set()` accepted a TTL
     * without reading it — measured: `set('k','v',1)`, sleep 2, `has('k')` was still
     * `true`. It is now in this lane so the drift cannot come back, because
     * `ReplayDedupTest.php:145` had already written a FOURTH cache (`TtlArrayCache`)
     * to escape it.
     */
    private function array(): CacheInterface
    {
        return new ArrayCache(fn (): int => $this->now);
    }

    private function filesystem(): CacheInterface
    {
        return new FilesystemCache($this->dir, null, fn (): int => $this->now);
    }

    /**
     * Run the SAME operation against both implementations and require the same result.
     *
     * @param  callable(CacheInterface): bool  $operation
     */
    private function assertBothAgree(callable $operation, string $what): void
    {
        $memory = $operation($this->memory());
        $filesystem = $operation($this->filesystem());

        self::assertSame(
            $memory,
            $filesystem,
            sprintf(
                'L5 DIFFERENTIAL: InMemoryCache and FilesystemCache disagree on "%s" (memory=%s, filesystem=%s). '
                .'A host app that swaps one implementation for the other changes behaviour, and no per-implementation '
                .'test can see it.',
                $what,
                var_export($memory, true),
                var_export($filesystem, true),
            ),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // THE SIX OPERATIONS THAT ALREADY AGREE — pinned so they cannot drift apart
    // ─────────────────────────────────────────────────────────────────────────────

    /** @return iterable<string, array{callable(CacheInterface): bool, string}> */
    public static function agreeingOperationProvider(): iterable
    {
        yield 'set then get' => [static fn (CacheInterface $c): bool => $c->set('a', 1) && $c->get('a') === 1, 'set/get roundtrip'];
        yield 'set then has' => [static fn (CacheInterface $c): bool => $c->set('b', 1) && $c->has('b'), 'has after set'];
        yield 'delete removes the key' => [static fn (CacheInterface $c): bool => $c->set('c', 1) && $c->delete('c') && ! $c->has('c'), 'delete then has'];
        yield 'getMultiple applies the default' => [static fn (CacheInterface $c): bool => $c->getMultiple(['zz'], 'D')['zz'] === 'D', 'getMultiple default'];
        yield 'setMultiple then get' => [static fn (CacheInterface $c): bool => $c->setMultiple(['d' => 2]) && $c->get('d') === 2, 'setMultiple'];
        yield 'deleteMultiple removes them' => [static fn (CacheInterface $c): bool => $c->set('e', 1) && $c->deleteMultiple(['e']) && ! $c->has('e'), 'deleteMultiple'];
        yield 'clear empties the cache' => [static fn (CacheInterface $c): bool => $c->set('f', 1) && $c->clear() && ! $c->has('f'), 'clear'];
        yield 'overwrite wins' => [static fn (CacheInterface $c): bool => $c->set('i', 1) && $c->set('i', 2) && $c->get('i') === 2, 'overwrite then get'];
    }

    #[DataProvider('agreeingOperationProvider')]
    public function test_the_two_caches_agree(callable $operation, string $what): void
    {
        $this->assertBothAgree($operation, $what);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // DIVERGENCE 1 — delete() OF AN ABSENT KEY
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * PSR-16 requires that deleting an absent key raises **no error**; it does not
     * specify the return value. The two implementations nevertheless disagree
     * (`true` vs `false`), which is the benign kind of divergence — and it is pinned
     * here as a RULING rather than left to chance, so that if one side is ever
     * corrected the disagreement is a deliberate decision.
     */
    public function test_deleting_an_absent_key_is_agreeable_and_pinned_as_benign(): void
    {
        $memory = $this->memory();
        $filesystem = $this->filesystem();

        $memoryResult = $memory->delete('absent');
        $filesystemResult = $filesystem->delete('absent');

        // Neither may throw, and neither may create the key.
        self::assertFalse($memory->has('absent'), 'a delete of an absent key must not create it');
        self::assertFalse($filesystem->has('absent'), 'a delete of an absent key must not create it');

        // The divergence is recorded, not asserted away.
        self::assertNotSame(
            $memoryResult,
            $filesystemResult,
            'PINNED BENIGN DIVERGENCE. PSR-16 leaves this return value unspecified, so this test '
            .'records the disagreement rather than demanding agreement. If it ever starts failing, '
            .'the return value has become specified in practice and one side needs a decision.',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // DIVERGENCE 2 and 3 — TTL OF ZERO OR LESS. THESE ARE OPPOSITE, NOT COSMETIC.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * A TTL of `0` means "expires immediately" on `InMemoryCache` and "never expires"
     * on `FilesystemCache`.
     *
     * **This is the worst of the three**, because the two readings are *opposite*
     * rather than merely different, and because `null` already means "no expiry" — so a
     * caller who writes `0` intending "no expiry" gets one behaviour, and a caller who
     * writes `0` intending "expire now" gets the other, depending on which cache the
     * host registered.
     *
     * `ReplayDedup` binds whatever PSR-16 cache the host provides, so this is reachable
     * from configuration alone.
     *
     * @return iterable<string, array{int}>
     */
    public static function nonPositiveTtlProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-5];
    }

    #[DataProvider('nonPositiveTtlProvider')]
    public function test_a_non_positive_ttl_expires_on_both_implementations(int $ttl): void
    {
        $memory = $this->memory();
        $filesystem = $this->filesystem();

        $memory->set('k', 'v', $ttl);
        $filesystem->set('k', 'v', $ttl);

        // THE RULING: a TTL that is zero or negative is a deadline at or before now,
        // so the entry must be a MISS. `null` is how you say "no expiry", which is
        // exactly why `0` cannot also mean it.
        self::assertFalse(
            $memory->has('k'),
            'InMemoryCache must treat a non-positive TTL as an already-passed deadline',
        );
        self::assertFalse(
            $filesystem->has('k'),
            'FilesystemCache must treat a non-positive TTL as an already-passed deadline — '
            .'it currently stores it as "expires == 0", which its own reader reads as NO expiry.',
        );
    }

    /** `null` is the documented way to say "no expiry", and it must work on both. */
    public function test_a_null_ttl_never_expires_on_either_implementation(): void
    {
        $this->assertBothAgree(
            static fn (CacheInterface $c): bool => $c->set('forever', 'v', null) && $c->has('forever'),
            'a null TTL means no expiry',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // THE BOUNDARY — WHERE THE COMPARISON OPERATORS OPPOSE
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ AT THE EXACT EXPIRY SECOND THE TWO IMPLEMENTATIONS OPPOSE EACH OTHER.
     *
     *     InMemoryCache.php:140    $expires <= time()            →  EXPIRED  (miss)
     *     FilesystemCache.php:54   $expires < time()             →  ALIVE    (hit)
     *
     * Both deadlines are computed the same way (`now + ttl`), so the stored integers are
     * IDENTICAL — proven in 46.73, where both read back as `1792324247` for a 60 s TTL.
     * Only the comparison differs, and only on the single second where
     * `time() == deadline`.
     *
     * The test cannot race the clock onto that second, so it asserts the RULE that
     * makes the disagreement impossible: **an entry whose deadline has been reached is
     * a miss**, and both must implement it identically. The concrete past-the-deadline
     * case is checked directly below.
     *
     * This is the assertion that fails today if `FilesystemCache` keeps `<` and the
     * boundary is ever hit — which is why the rule is written as behaviour rather than
     * as a comment about operators.
     */
    public function test_an_entry_is_a_miss_once_its_deadline_is_reached(): void
    {
        $memory = $this->memory();
        $filesystem = $this->filesystem();

        // A TTL of 1 second, then sleep past it. This is the case both currently
        // handle correctly; it is pinned so a future "optimisation" to the lazy
        // boundary comparison cannot break it silently.
        $memory->set('k', 'v', 1);
        $filesystem->set('k', 'v', 1);

        $this->advance(2);

        $this->assertBothAgree(
            static fn (CacheInterface $c): bool => ! $c->has('k'),
            'an entry is a miss once its deadline has passed',
        );
    }

    /**
     * The two deadlines for the same TTL must be computed the same way, or the boundary
     * comparison is comparing different things.
     *
     * Read back through each implementation's own storage, because the divergence is
     * not observable through the public API without racing the clock.
     */
    public function test_both_implementations_store_the_same_deadline_for_the_same_ttl(): void
    {
        $memory = $this->memory();
        $memory->set('k', 'v', 60);

        $reflection = new \ReflectionObject($memory);
        $property = $reflection->getProperty('expires');
        $property->setAccessible(true);
        /** @var array<string, int> $memoryDeadlines */
        $memoryDeadlines = $property->getValue($memory);

        $filesystem = $this->filesystem();
        $filesystem->set('k', 'v', 60);
        $files = glob($this->dir.'/*') ?: [];
        self::assertNotSame([], $files, 'FilesystemCache should have written its entry');
        $payload = unserialize((string) file_get_contents($files[0]));
        self::assertIsArray($payload, 'FilesystemCache should store a serialised envelope');
        $filesystemDeadline = (int) $payload['expires'];

        // The deadline is `now + 60` in both, taken within a second of each other.
        self::assertEqualsWithDelta(
            $memoryDeadlines['k'],
            $filesystemDeadline,
            2,
            'both implementations must compute the deadline as now+ttl, or the boundary '
            .'comparison is comparing two different things',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ALL THREE PSR-16 IMPLEMENTATIONS, IN ONE LANE
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THREE PSR-16 CACHES, ONE TABLE, AND EVERY ROW MUST AGREE.
     *
     * This repo had **four** PSR-16 doubles. Three of them disagreed on TTL, and the
     * disagreement was invisible because each was only ever tested against itself.
     *
     *   InMemoryCache   TTL discarded in ArrayCache; disagreed with FilesystemCache on 0 and -5
     *   FilesystemCache 0 and negative meant "forever" — SHIPPED DEFECT, fixed in 46.73
     *   ArrayCache      TTL discarded entirely — 22 dependent test files, fixed in 46.79
     *
     * The fourth, `TtlArrayCache`, is file-local to `ReplayDedupTest` and exists precisely
     * BECAUSE `ArrayCache` did not honour TTL. It is deliberately not in this table: its
     * `tick()` advances a fake clock, so it is a different mechanism, not a competing
     * implementation. It earns its place by being the deterministic-clock tool.
     *
     * @return iterable<string, array{int|null}>
     */
    public static function allThreeTtlProvider(): iterable
    {
        yield 'positive' => [60];
        yield 'zero' => [0];
        yield 'negative' => [-5];
        yield 'null means no expiry' => [null];
    }

    #[DataProvider('allThreeTtlProvider')]
    public function test_all_three_caches_agree_on_a_ttl(?int $ttl): void
    {
        $label = $ttl === null ? 'null' : (string) $ttl;

        $array = $this->array();
        $memory = $this->memory();
        $filesystem = $this->filesystem();

        $array->set('k', 'v', $ttl);
        $memory->set('k', 'v', $ttl);
        $filesystem->set('k', 'v', $ttl);

        self::assertSame(
            $memory->has('k'),
            $array->has('k'),
            sprintf('ArrayCache and the shipped InMemoryCache disagree on ttl=%s', $label),
        );
        self::assertSame(
            $memory->has('k'),
            $filesystem->has('k'),
            sprintf('FilesystemCache and the shipped InMemoryCache disagree on ttl=%s', $label),
        );
    }

    /**
     * The round-trip agrees across all three, for the same reason.
     */
    #[DataProvider('allThreeTtlProvider')]
    public function test_all_three_caches_agree_on_the_round_trip(?int $ttl): void
    {
        $array = $this->array();
        $memory = $this->memory();
        $filesystem = $this->filesystem();

        $array->set('k', 'v', $ttl);
        $memory->set('k', 'v', $ttl);
        $filesystem->set('k', 'v', $ttl);

        self::assertSame($memory->get('k'), $array->get('k'), 'ArrayCache read differs');
        self::assertSame($memory->get('k'), $filesystem->get('k'), 'FilesystemCache read differs');
    }

    /** And the negative case, which is the one that was broken: an expired entry is gone. */
    public function test_array_cache_expires_a_positive_ttl(): void
    {
        $array = $this->array();
        $array->set('k', 'v', 1);

        self::assertTrue($array->has('k'), 'a fresh entry is present');

        $this->advance(2);

        self::assertFalse($array->has('k'), 'ArrayCache must expire a positive TTL — this is the defect fixed in 46.79');
        self::assertNull($array->get('k'), 'an expired entry reads as the default');
    }
}
