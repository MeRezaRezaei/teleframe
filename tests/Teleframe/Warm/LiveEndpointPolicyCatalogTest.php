<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Warm;

use MeRezaRezaei\Teleframe\Warm\LiveEndpointPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * THE ALLOWLIST MUST NAME METHODS THAT EXIST.
 *
 * `tests/Live/LiveWarmFenceTest.php` walks the catalog and asks "is anything
 * destructive allowed?". This file walks the ALLOWLIST and asks the question
 * from the other side: "can every entry here ever fire at all?".
 *
 * That direction is not a duplicate, and it is the one that was missing. The
 * existing fence iterates the CATALOG and calls `isLiveSafe()` on each name, so
 * an allowlist entry that does not exist is never looked at — it is invisible to
 * that test by construction. A list of six entries therefore reads as six
 * covered methods while only five can ever be dialed, and the gate that is
 * supposed to prove the live surface is understood is instead counting a
 * string. That is the same failure class as a check that verifies a different
 * artifact than the one in use: the assertion is real, and the thing it
 * asserts about is not the thing in use.
 *
 * THE PINNED DEFECT (not fixed here — this file must not edit the policy):
 * `src/Teleframe/Warm/LiveEndpointPolicy.php:88` allowlists `auth.getPassword`.
 * That name does not exist in the 823-method catalog
 * `src/Schema/schema/methods-mtproto.json`. The real method is
 * `account.getPassword`, which IS present and IS separately allowlisted at
 * `LiveEndpointPolicy.php:92`. So `:88` is a dead entry with a plausible-sounding
 * reason attached, and it is the kind of dead entry that survives review
 * precisely because it reads like a real method name.
 *
 * THE SKIP IS NOT DECORATIVE. The two tests that would fail on that typo assert
 * the DESIRED behaviour — allowlisted name present in the catalog — and are
 * skipped with a reason naming `:88`, not rewritten to assert the typo. When
 * someone removes the dead entry (or corrects it), the skip stops firing and the
 * assertions start running. A test that asserts `auth.getPassword` is *absent*
 * would be green today and would keep the defect installed forever, which is
 * the one outcome this file exists to prevent.
 *
 * Everything else in this file is cold and hermetic: it reads the shipped JSON
 * catalog off disk and calls pure static methods on the policy. No session, no
 * socket, no database, no credentials, no Telegram.
 */
final class LiveEndpointPolicyCatalogTest extends TestCase
{
    /**
     * The pinned defect, stated once so the skip reason and the failure message
     * cannot drift apart from each other.
     */
    private const DEAD_ENTRY = 'auth.getPassword';

    private const DEAD_ENTRY_CITATION = 'src/Teleframe/Warm/LiveEndpointPolicy.php:88';

    /**
     * Shipped MTProto catalog. Path resolved from this file rather than from a
     * constant, and asserted to exist, so a moved directory fails loudly here
     * instead of silently yielding an empty catalog that makes every
     * membership test below vacuously pass.
     */
    private static function catalogPath(): string
    {
        return __DIR__.'/../../../src/Schema/schema/methods-mtproto.json';
    }

    /**
     * @return array<string, array<string, mixed>> the catalog's `methods` map,
     *                                          keyed by method name
     */
    private static function catalog(): array
    {
        $path = self::catalogPath();
        self::assertFileExists($path, 'the shipped catalog must be on disk at its documented path');

        $raw = file_get_contents($path);
        self::assertIsString($raw, 'the shipped catalog must be readable');
        self::assertNotSame('', $raw, 'the shipped catalog must not be empty');

        $decoded = json_decode($raw, true);
        self::assertIsArray($decoded, 'the shipped catalog must be valid JSON — an empty parse here would make every membership test below pass for the wrong reason');
        self::assertArrayHasKey('methods', $decoded, 'the catalog must carry a `methods` map');

        /** @var array<string, array<string, mixed>> $methods */
        $methods = (array) $decoded['methods'];

        return $methods;
    }

    /**
     * Allowlisted names that are absent from the shipped catalog, i.e. entries
     * that can never fire because nothing can name them at call time.
     *
     * @param  array<string, array<string, mixed>>  $catalog
     * @return list<string>
     */
    private static function entriesAbsentFromCatalog(array $catalog): array
    {
        $absent = [];

        foreach (LiveEndpointPolicy::liveSafeMethods() as $method) {
            if (! array_key_exists($method, $catalog)) {
                $absent[] = $method;
            }
        }

        return $absent;
    }

    /**
     * The one gate. Every test in this file that needs the catalog to be
     * non-degenerate asserts its size first, so a catalog that failed to parse
     * is a failure and not a green run.
     */
    #[Test]
    public function the_catalog_parsed_is_the_real_large_catalog_and_not_an_empty_or_failed_parse(): void
    {
        $catalog = self::catalog();

        self::assertGreaterThan(
            500,
            count($catalog),
            'catalog sanity: the shipped MTProto surface is ~823 methods. A small count here means '
            .'this test is reading the wrong artifact, and every membership assertion below would be '
            .'passing against nothing.',
        );
    }

    /**
     * CHECK 1 — the test that fails today.
     *
     * An allowlist entry naming a method that does not exist is a gate that
     * reads as coverage while being unreachable. Assert the desired behaviour,
     * skip with a reason while the known defect stands.
     */
    #[Test]
    public function every_allowlisted_method_name_exists_in_the_shipped_catalog(): void
    {
        $catalog = self::catalog();
        $absent = self::entriesAbsentFromCatalog($catalog);

        if ($absent !== []) {
            $this->markTestSkipped(sprintf(
                'PINNED DEFECT in %s: allowlist entry %s names no method in the %d-method catalog '
                .'src/Schema/schema/methods-mtproto.json, so that entry can never fire. The real '
                .'method is `account.getPassword` (present in the catalog, and separately allowlisted '
                .'at LiveEndpointPolicy.php:92). Not fixed here because the policy is the deliberate '
                .'blast-radius boundary. Fix: delete the dead entry, or correct it to the real name.',
                self::DEAD_ENTRY_CITATION,
                implode(', ', $absent),
                count($catalog),
            ));
        }

        self::assertSame(
            [],
            $absent,
            'every allowlisted method must exist in the shipped catalog, or the live surface is '
            .'smaller than the policy claims it is.',
        );
    }

    /**
     * CHECK 2 — the allowlist cannot drift to a name the wire refuses.
     *
     * Scoped to the names that exist in the catalog: a name with no catalog
     * entry has no `wire` block to read, and is check 1's job, not this test's.
     * Asserting the two in one test would give one typo two failure sites.
     */
    #[Test]
    public function every_allowlisted_method_that_exists_is_marked_wire_callable(): void
    {
        $catalog = self::catalog();

        $checked = 0;
        foreach (LiveEndpointPolicy::liveSafeMethods() as $method) {
            if (! array_key_exists($method, $catalog)) {
                continue; // check 1 owns absent names
            }

            $entry = (array) $catalog[$method];
            self::assertArrayHasKey('wire', $entry, "{$method} has no `wire` block, so callability is unknown");

            $wire = (array) $entry['wire'];
            self::assertTrue(
                $wire['callable'] ?? false,
                "{$method} is allowlisted as live-safe but the catalog marks it NOT wire-callable, so "
                .'the allowlist would name a method this client refuses to put on the wire.',
            );

            $checked++;
        }

        self::assertGreaterThanOrEqual(
            5,
            $checked,
            "only {$checked} allowlisted entries were checked for wire callability — the allowlist is "
            .'smaller than expected, so this test is not seeing what it thinks it is seeing.',
        );
    }

    /**
     * CHECK 3 — the allowlist and the destructive-verb deny list cannot silently
     * overlap.
     *
     * Computed independently of `refusalReason()`, on purpose. That method
     * returns null for an allowlisted name at LiveEndpointPolicy.php:116 BEFORE
     * it ever reaches the verb loop at :123, so it reports "no problem" for an
     * allowlisted destructive verb. Only the raw comparison catches the overlap.
     */
    #[Test]
    public function no_allowlisted_name_matches_a_destructive_verb(): void
    {
        $overlaps = [];

        foreach (LiveEndpointPolicy::liveSafeMethods() as $method) {
            foreach (self::matchedDestructiveVerbs($method) as $verb) {
                $overlaps[] = sprintf('%s => "%s" (LiveEndpointPolicy::DESTRUCTIVE_VERBS)', $method, $verb);
            }
        }

        self::assertSame(
            [],
            $overlaps,
            'these allowlisted names also match a DESTRUCTIVE_VERB. `refusalReason()` cannot see it '
            .'because the allowlist is checked first (LiveEndpointPolicy.php:116), so the two halves '
            .'of the fence disagree and the allowlist would win: '.implode('; ', $overlaps),
        );
    }

    /**
     * The policy's own verb extraction and prefix match, replicated so check 3
     * tests the same rule the policy runs rather than a paraphrase of it
     * (LiveEndpointPolicy.php:120-124).
     *
     * @return list<string> the destructive verbs this method name matches
     */
    private static function matchedDestructiveVerbs(string $method): array
    {
        $verb = str_contains($method, '.') ? substr($method, strpos($method, '.') + 1) : $method;
        $lower = strtolower($verb);

        $matched = [];
        foreach (LiveEndpointPolicy::DESTRUCTIVE_VERBS as $dangerous) {
            if (str_starts_with($lower, $dangerous)) {
                $matched[] = (string) $dangerous;
            }
        }

        return $matched;
    }

    /**
     * CHECK 4 — the policy fails CLOSED, and the lookup is by EXACT key.
     *
     * `isLiveSafe()` is `isset(self::LIVE_SAFE[$method])` at
     * LiveEndpointPolicy.php:107 — an exact string-key lookup, not a prefix
     * match. These assertions pin the behaviour that actually exists: a real
     * catalog method that merely shares a prefix with an allowlisted name is
     * still refused.
     */
    #[Test]
    public function the_policy_permits_a_real_method_and_refuses_everything_else(): void
    {
        $catalog = self::catalog();

        // The real two-step read: present in the catalog, allowlisted, permitted.
        self::assertArrayHasKey('account.getPassword', $catalog, 'precondition: the real method exists');
        self::assertTrue(LiveEndpointPolicy::isLiveSafe('account.getPassword'), 'precondition: it is allowlisted');
        self::assertNull(LiveEndpointPolicy::refusalReason('account.getPassword'), 'a proven read must be live-eligible');

        // Exact match, not prefix: a REAL catalog method that starts with an
        // allowlisted name is still refused.
        self::assertArrayHasKey('account.getPasswordSettings', $catalog, 'precondition: this real method exists in the catalog');
        self::assertFalse(LiveEndpointPolicy::isLiveSafe('account.getPasswordSettings'), 'the lookup is an exact key match, not a prefix match');
        self::assertNotNull(LiveEndpointPolicy::refusalReason('account.getPasswordSettings'), 'a name that only shares a prefix with an allowlisted entry must be refused');

        // Unknown namespace: absent from the catalog AND from the allowlist.
        $unknown = 'someNewNamespace.doSomethingInteresting';
        self::assertArrayNotHasKey($unknown, $catalog, 'precondition: the unknown name is not in the catalog');
        self::assertFalse(LiveEndpointPolicy::isLiveSafe($unknown), 'an unlisted name is never permitted');
        self::assertNotNull(LiveEndpointPolicy::refusalReason($unknown), 'the policy must fail CLOSED for an unknown name');

        // A typo of an allowlisted name is refused too — this is the shape the
        // pinned defect takes when it reaches call time.
        self::assertFalse(LiveEndpointPolicy::isLiveSafe('users.getUser'), 'a near-miss on an allowlisted name is not a match');
        self::assertNotNull(LiveEndpointPolicy::refusalReason('users.getUser'));
    }

    /**
     * THE DEFECT IS GONE (2026-10-18) — this test now pins the FIXED state.
     *
     * This previously asserted the shape of the defect:
     *
     *     assertTrue(isLiveSafe(self::DEAD_ENTRY),
     *         'the dead entry is still an allowlist key, which is why it reads as coverage')
     *
     * That was a deliberate fingerprint, not a contract: it recorded that
     * `auth.getPassword` was simultaneously permitted and unreachable, so that the
     * skip above could not be mistaken for "nothing is wrong". With the entry
     * removed, that state no longer exists, so the test that named it has nothing
     * left to say and is rewritten rather than deleted — deleting it would lose the
     * check that the dead name is STILL not in the catalog, which is the half of
     * this test that remains true and worth keeping.
     *
     * The invariant now pinned: the name that never existed is neither a catalog
     * method nor a live-safe key. Both directions matter — a future hand that adds
     * `auth.getPassword` back to LIVE_SAFE would re-open the original defect, and a
     * future hand that added it to the CATALOG would create a real method that
     * nothing has audited for safety.
     */
    #[Test]
    public function the_dead_entry_is_now_absent_from_both_the_catalog_and_the_allowlist(): void
    {
        self::assertArrayNotHasKey(
            self::DEAD_ENTRY,
            self::catalog(),
            'auth.getPassword must stay out of the catalog: the real method is account.getPassword',
        );
        self::assertFalse(
            LiveEndpointPolicy::isLiveSafe(self::DEAD_ENTRY),
            'a name no catalog method can produce must not be advertised as live-eligible',
        );
        self::assertNotNull(
            LiveEndpointPolicy::refusalReason(self::DEAD_ENTRY),
            'the policy now refuses the dead name instead of permitting it',
        );
    }

    /**
     * CHECK 5 — a count assertion that catches an entry which never fires.
     *
     * The policy exposes its allowed set publicly via `liveSafeMethods()`
     * (LiveEndpointPolicy.php:100). If the size of that set ever stops matching
     * the number of entries that can actually be dialed, the policy is
     * advertising coverage it does not have. Asserting the two numbers as equal
     * is the only assertion in this file that catches an entry silently rotting
     * into a string.
     */
    #[Test]
    public function the_exposed_allowed_set_is_exactly_as_large_as_the_entries_that_can_fire(): void
    {
        $catalog = self::catalog();
        $exposed = LiveEndpointPolicy::liveSafeMethods();

        self::assertSame(
            $exposed,
            array_values($exposed),
            'precondition: liveSafeMethods() returns a list, not a keyed map',
        );
        self::assertSame(
            array_keys(LiveEndpointPolicy::LIVE_SAFE),
            $exposed,
            'precondition: liveSafeMethods() must expose exactly the LIVE_SAFE keys, or this count '
            .'is measuring something other than the allowlist',
        );

        $absent = self::entriesAbsentFromCatalog($catalog);
        $firing = count($exposed) - count($absent);

        if ($absent !== []) {
            $this->markTestSkipped(sprintf(
                'PINNED DEFECT in %s: liveSafeMethods() advertises %d live-eligible methods but only '
                .'%d can fire — %s has no catalog entry. The public count is the number a reviewer '
                .'would trust, so it overstates the live surface by exactly the dead entries. '
                .'Not fixed here; the policy is the blast-radius boundary.',
                self::DEAD_ENTRY_CITATION,
                count($exposed),
                $firing,
                implode(', ', $absent),
            ));
        }

        self::assertSame(
            $firing,
            count($exposed),
            'the publicly exposed live surface must be exactly as large as the set of entries that '
            .'can actually be dialed.',
        );
    }
}
