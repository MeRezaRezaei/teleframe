<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use DateInterval;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * The replay-dedup window is only time-bounded if the store behind it
 * honours a TTL, and `InMemoryCache` is the SHIPPED DEFAULT behind
 * `ReplayDedup` whenever the host binds no PSR-16 cache. It used to accept
 * `$ttl` and discard it, so `ReplayDedup`'s window spanned the whole
 * process instead of its documented window. These tests pin the contract
 * in both directions — a TTL that lapses, and a TTL-less entry that never
 * does.
 */
final class InMemoryCacheTtlTest extends TestCase
{
    /** Long enough that no sleep in this file can accidentally reach it. */
    private const IMMORTAL_SECONDS = 60;

    private InMemoryCache $cache;

    /** Fake epoch second the injectable clock returns; advanced only by advance(). */
    private int $now = 1_000_000;

    private function advance(int $seconds): void
    {
        $this->now += $seconds;
    }

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache(fn (): int => $this->now);
    }

    // --- gate 1: an int TTL actually lapses ---------------------------------

    public function test_int_ttl_entry_is_a_miss_once_it_lapses(): void
    {
        self::assertTrue($this->cache->set('k', 'v', 1));
        self::assertTrue($this->cache->has('k'), 'live entry must be a hit');
        self::assertSame('v', $this->cache->get('k'));

        $this->advance(2);

        self::assertFalse($this->cache->has('k'), 'a lapsed TTL must be a miss for has()');
        self::assertNull($this->cache->get('k'), 'a lapsed TTL must be a miss for get()');
        self::assertSame('fallback', $this->cache->get('k', 'fallback'));
    }

    // --- gate 2: no TTL never lapses (the regression half) ------------------

    public function test_entry_without_ttl_survives_past_any_window(): void
    {
        $this->cache->set('immortal', 'v');

        $this->advance(2);

        self::assertTrue($this->cache->has('immortal'), 'a null TTL must never evict');
        self::assertSame('v', $this->cache->get('immortal'));
    }

    public function test_a_lapsed_key_does_not_evict_a_neighbour_written_without_ttl(): void
    {
        $this->cache->set('short', 'v', 1);
        $this->cache->set('immortal', 'v');

        $this->advance(2);

        self::assertFalse($this->cache->has('short'));
        self::assertTrue($this->cache->has('immortal'));
    }

    public function test_rewriting_a_key_without_a_ttl_clears_a_previous_ttl(): void
    {
        $this->cache->set('k', 'first', 1);
        $this->cache->set('k', 'second');

        $this->advance(2);

        self::assertSame('second', $this->cache->get('k'), 'the TTL of the older write must not survive the newer one');
    }

    // --- gate 3: the DateInterval form is honoured identically --------------

    public function test_date_interval_ttl_entry_is_a_miss_once_it_lapses(): void
    {
        self::assertTrue($this->cache->set('k', 'v', new DateInterval('PT1S')));

        $this->advance(2);

        self::assertFalse($this->cache->has('k'));
        self::assertNull($this->cache->get('k'));
    }

    public function test_date_interval_ttl_is_live_before_it_lapses(): void
    {
        self::assertTrue($this->cache->set('k', 'v', new DateInterval('PT'.self::IMMORTAL_SECONDS.'S')));

        self::assertTrue($this->cache->has('k'));
        self::assertSame('v', $this->cache->get('k'));
    }

    // --- gate 4: the delete / clear / multiple paths -------------------------

    public function test_delete_on_an_expired_key_returns_true_and_leaves_it_a_miss(): void
    {
        $this->cache->set('k', 'v', 1);

        $this->advance(2);

        // PSR-16: delete() must not raise on a missing key and returns true.
        self::assertTrue($this->cache->delete('k'));
        self::assertFalse($this->cache->has('k'), 'delete must not resurrect a lapsed key');
        self::assertNull($this->cache->get('k'));

        // Idempotent, exactly as for a never-present key.
        self::assertTrue($this->cache->delete('k'));
    }

    public function test_delete_multiple_tolerates_expired_keys(): void
    {
        $this->cache->set('a', 1, 1);
        $this->cache->set('b', 2);

        $this->advance(2);

        self::assertTrue($this->cache->deleteMultiple(['a', 'b']));
        self::assertFalse($this->cache->has('a'));
        self::assertFalse($this->cache->has('b'));
    }

    /**
     * Four keys, three states, and the states are ASSERTED rather than
     * assumed — which is the whole difference from the version this replaces.
     *
     * That version wrote both keys and called `clear()` in the same instant,
     * so `a` was still LIVE: every assertion was satisfied by one and the same
     * code path (`clear()` empties the store), the TTL dimension played no
     * part at all, and a cache that ignored TTLs outright sailed through it.
     *
     * Expiry is LAZY, so reading an expired key to prove the premise IS the
     * eviction — `expired` would be gone before `clear()` was ever called.
     * The premise is therefore carried by two witnesses that are read, and
     * which between them state what `expired` is without touching it:
     *
     *   - `probe`    — read, and a MISS, or a TTL is not being honoured at all
     *   - `expired`  — never read: lapsed, so still physically present
     *   - `lapsing`  — LIVE and TTL-bearing (read, and a HIT)
     *   - `immortal` — LIVE and TTL-less
     *
     * KNOWN LIMIT: no read can tell "`clear()` removed the lapsed `expired`"
     * from "the lazy lapse removed it anyway", so that one direction is not
     * observable through a PSR-16 store with lazy expiry and is not claimed.
     * The live half of the name is fully falsifiable — a `clear()` that
     * spared either live key fails — and the TTL dimension is now asserted
     * rather than absent, which is what the old test had none of.
     */
    public function test_clear_drops_expired_and_live_entries_alike(): void
    {
        $this->cache->set('expired', 1, 1);
        $this->cache->set('probe', 9, 1);
        $this->cache->set('lapsing', 2, self::IMMORTAL_SECONDS);
        $this->cache->set('immortal', 3);

        $this->advance(2);

        self::assertFalse($this->cache->has('probe'), 'the premise: a 1-second TTL has lapsed');
        self::assertTrue($this->cache->has('lapsing'), 'the premise: a 60-second TTL has not');

        self::assertTrue($this->cache->clear());

        self::assertFalse($this->cache->has('expired'), 'a lapsed, never-read entry must not come back');
        self::assertFalse($this->cache->has('lapsing'), 'a live entry must be dropped, TTL or not');
        self::assertFalse($this->cache->has('immortal'), 'a TTL-less live entry must be dropped too');
    }

    public function test_get_multiple_reports_an_expired_key_as_missing(): void
    {
        $this->cache->set('a', 1, 1);
        $this->cache->set('b', 2);

        $this->advance(2);

        self::assertSame(
            ['a' => 'fallback', 'b' => 2],
            $this->cache->getMultiple(['a', 'b'], 'fallback'),
        );
    }

    public function test_set_multiple_forwards_its_ttl_to_every_key(): void
    {
        self::assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2], 1));

        $this->advance(2);

        self::assertFalse($this->cache->has('a'));
        self::assertFalse($this->cache->has('b'));
    }

    public function test_a_zero_ttl_lapses_immediately(): void
    {
        self::assertTrue($this->cache->set('k', 'v', 0));

        self::assertFalse($this->cache->has('k'), 'expires-in-zero-seconds means gone now');
    }

    // --- the promise the docblock makes: the window is time-bounded --------

    /**
     * The defect in its consumer shape: `ReplayDedup` promised a
     * time-bounded cross-run window. Two dispatchers, one shared store, one
     * TTL — a second run inside the window is a replay drop, a run after it
     * is accepted. With the TTL discarded this could never pass.
     */
    public function test_replay_dedup_window_reopens_after_the_ttl_elapses(): void
    {
        $cache = new InMemoryCache(fn (): int => $this->now);
        $registry = new HandlerRegistry;
        $ran = 0;
        $registry->onMessage(function (Update $u) use (&$ran): void {
            $ran++;
        });

        $update = Update::fromBus(['_' => 'updateNewMessage', 'message' => 'same'], 7, 1000);

        $first = new UpdateDispatcher($registry, new Pipeline, new ArrayContainer, $cache, dedupTtl: 1, dedupCache: $cache);
        $first->dispatch($update);
        $first->dispatch($update);   // inside the window -> dropped

        self::assertSame(1, $ran);
        self::assertSame(1, $first->seenReplays(), 'the second run must count a replay drop');

        $this->advance(2);

        // A NEW processing run against the SAME store: the window has closed,
        // so the identical payload is accepted again rather than suppressed
        // for the life of the process.
        $second = new UpdateDispatcher($registry, new Pipeline, new ArrayContainer, $cache, dedupTtl: 1, dedupCache: $cache);
        $second->dispatch($update);

        self::assertSame(2, $ran, 'the cross-run window must reopen once the TTL elapses');
        self::assertSame(0, $second->seenReplays());
    }
}
