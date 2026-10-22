<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;

/**
 * L5 — THE DIFFERENTIAL FOR `FakeStoreApi`, WHICH HAS NO INTERFACE AT ALL.
 *
 * ## WHY THIS ONE IS DIFFERENT FROM EVERY OTHER LANE
 *
 * The other four lanes compare two implementations of a shared interface. This one
 * cannot. `FakeStoreApi` implements **nothing** — it is an
 * `array<string, callable>` map handed to `TelegramStoreChannel::__construct()`, and the
 * ONLY mechanical coupling is a name check:
 *
 *     // src/Teleframe/Store/TelegramStoreChannel.php:124-131
 *     foreach (self::API_KEYS as $key) {
 *         if (! array_key_exists($key, $this->api)) { $missing[] = $key; }
 *     }
 *
 * ⛔ **So ARITY AND RETURN SHAPE ARE ENTIRELY UNCONSTRAINED.** A double whose
 * `searchMessages` takes two arguments and returns a string passes the guard, and the
 * caller cannot tell.
 *
 * ## AND HERE IS WHAT THAT COSTS, MEASURED
 *
 * Two of the ten callables feed their return value straight into iteration
 * (`TelegramStoreChannel.php:609` and `:907`):
 *
 *     $page = ($this->api['historyPage'])(…);
 *     $page = is_array($page) ? array_values(array_filter($page, 'is_array')) : [];
 *
 *     $rows = ($this->api['searchMessages'])(…);
 *     return is_array($rows) ? $rows : [];
 *
 * Both **degrade a wrong shape to `[]` rather than failing.** Measured:
 *
 *     wrong type (string)                 -> caller sees []
 *     right type, wrong inner (scalars)   -> caller sees []
 *     correct shape                       -> caller sees [{"id":1},{"id":2}]
 *
 * ⛔ **AND AN EMPTY BUCKET IS ALSO `[]`.** So a double that returns the wrong shape and a
 * store that genuinely has no messages are **indistinguishable to every test in the
 * repository**. A backfill that silently sees "nothing to restore" is green.
 *
 * That is the L5 failure in its purest form: there is no second implementation to compare
 * against, so the contract has to be asserted from the CALL SITE instead.
 */
final class StoreApiShapeDifferentialTest extends TestCase
{
    /**
     * The ten callables the channel requires, read from `API_KEYS`
     * (`TelegramStoreChannel.php:72-83`).
     *
     * @return iterable<string, array{string}>
     */
    public static function apiKeyProvider(): iterable
    {
        foreach ([
            'findChannel', 'createChannel', 'uploadPart', 'sendDocument', 'sendReference',
            'sendMessage', 'searchMessages', 'historyPage', 'deleteMessages', 'getFile',
        ] as $key) {
            yield $key => [$key];
        }
    }

    /** The keys, read from the class rather than restated here. */
    private static function productionKeys(): array
    {
        $reflection = new ReflectionClass(TelegramStoreChannel::class);
        $constant = $reflection->getReflectionConstant('API_KEYS');

        self::assertNotNull($constant, 'TelegramStoreChannel must declare API_KEYS — this lane reads it from there');

        /** @var list<string> $keys */
        $keys = $constant->getValue();

        return $keys;
    }

    private function api(): FakeStoreApi
    {
        return new FakeStoreApi;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 1 — THE NAME CONTRACT, which IS enforced, proven from both sides
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THE DOUBLE MUST SUPPLY EXACTLY THE KEYS THE CHANNEL REQUIRES — no more, no fewer.
     *
     * This is the one part of the contract that the constructor guard actually checks, so
     * it is the one part that can be asserted with confidence. A key the channel does not
     * need is a key nobody maintains, and a key it needs but the double lacks is a test
     * that cannot reach a real code path.
     */
    public function test_the_double_supplies_exactly_the_required_api_keys(): void
    {
        $required = self::productionKeys();
        $provided = array_keys($this->api()->apiMap());

        self::assertSame(
            [],
            array_values(array_diff($provided, $required)),
            'the double supplies keys the channel never calls — nobody maintains them',
        );
        self::assertSame(
            [],
            array_values(array_diff($required, $provided)),
            'the double is MISSING a key the channel requires — every test using it cannot reach that path',
        );
    }

    /** Every supplied key is callable — the guard checks presence, not callability. */
    #[DataProvider('apiKeyProvider')]
    public function test_every_required_key_is_callable(string $key): void
    {
        $map = $this->api()->apiMap();

        self::assertArrayHasKey($key, $map);
        self::assertIsCallable(
            $map[$key],
            sprintf('"%s" must be callable — the constructor guard checks array_key_exists, not callability', $key),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2 — THE SHAPE CONTRACT, which is NOT enforced, and the silent degradation
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ⛔ THE TWO CALLABLES WHOSE RETURN VALUE IS ITERATED MUST RETURN ROWS.
     *
     * `historyPage` and `searchMessages` feed straight into iteration with a guard that
     * turns anything wrong into `[]`. So the double must return the row shape, and this
     * test asserts it returns the SHAPE the caller can actually use — a list of row
     * arrays, not a list of scalars and not a string.
     */
    #[DataProvider('rowReturningKeyProvider')]
    public function test_the_row_returning_callables_return_a_list_of_rows(string $key, string $caller): void
    {
        $api = $this->api();
        // SEEDED, because an unseeded double correctly returns [] and that proves
        // nothing about shape. My first version asserted non-emptiness against an empty
        // double and read the failure as a shape defect — it was a missing fixture.
        $api->peerMessages[1] = [
            ['id' => 2, 'name' => 'prefix-two', 'date' => 2, 'message' => 'two'],
            ['id' => 1, 'name' => 'prefix-one', 'date' => 1, 'message' => 'one'],
        ];

        $map = $api->apiMap();
        $rows = $key === 'historyPage'
            ? ($map['historyPage'])($this->peer(), 10, 0)
            : ($map['searchMessages'])($this->peer(), 'prefix', 10, 0);

        self::assertIsArray(
            $rows,
            sprintf('"%s" must return an array — the caller does is_array(\$rows) ? \$rows : [] and a wrong type becomes []', $key),
        );
        self::assertNotSame(
            [],
            $rows,
            sprintf(
                '"%s" returned []. Nothing distinguishes "no messages" from "the double returned the '
                .'wrong shape" — %s silently treats both as an empty bucket.',
                $key,
                $caller,
            ),
        );

        foreach ($rows as $i => $row) {
            self::assertIsArray(
                $row,
                sprintf('"%s" row %d must be an array; the caller filters non-array rows away', $key, $i),
            );
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function rowReturningKeyProvider(): iterable
    {
        yield 'historyPage' => ['historyPage', 'TelegramStoreChannel.php:609'];
        yield 'searchMessages' => ['searchMessages', 'TelegramStoreChannel.php:907'];
    }

    /**
     * ⛔ AND THE DEGRADATION ITSELF IS PINNED, so the shape requirement has a reason
     * attached rather than looking arbitrary.
     *
     * These are the exact expressions from the two call sites. The point is that a wrong
     * shape is INDISTINGUISHABLE from an empty result — which is why the test above
     * demands a non-empty, well-shaped return rather than merely "an array".
     */
    public function test_a_wrong_shaped_return_is_indistinguishable_from_an_empty_result(): void
    {
        $asCallerSees = static fn (mixed $raw): array => is_array($raw)
            ? array_values(array_filter($raw, 'is_array'))
            : [];

        self::assertSame([], $asCallerSees('not-an-array'), 'a wrong TYPE becomes []');
        self::assertSame([], $asCallerSees([1, 2, 3]), 'wrong INNER SHAPE becomes []');
        self::assertSame([], $asCallerSees([]), 'and a genuinely empty result is []');
        self::assertSame(
            [],
            $asCallerSees('not-an-array'),
            'THE GAP, stated as a fact: all three of these are indistinguishable to the caller, '
            .'so a test asserting "no messages" cannot tell an empty bucket from a lying double.',
        );
    }

    /**
     * ⛔ THE FINDCHANNEL RETURN IS THE OTHER SHARP EDGE.
     *
     * `TelegramStoreChannel.php:745` reads `($this->api['findChannel'])($title)` and then
     * branches on its truthiness, so a bare int and an `[id, access_hash]` pair are not
     * interchangeable — and the guard cannot tell them apart.
     */
    public function test_find_channel_returns_a_pair_not_a_bare_scalar(): void
    {
        $api = $this->api();
        // The double answers `?array`, so an UNSEEDED lookup correctly returns null —
        // my first version read that as "a bare scalar" and was wrong. Seed it so the
        // assertion is about the found-pair shape, which is the thing that matters.
        $api->channels['teleframe-store:bucket'] = ['id' => 100, 'access_hash' => 42];

        $map = $api->apiMap();
        $found = ($map['findChannel'])('teleframe-store:bucket');

        self::assertIsArray(
            $found,
            'findChannel must return the [id, access_hash] pair the caller destructures — a bare '
            .'int passes the constructor guard and then produces a channel with no access hash.',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3 — THE PRODUCTION SIDE, read from source so the lane cannot drift
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * The guard checks NAMES ONLY, and that is the fact this whole lane rests on.
     *
     * If someone strengthens the constructor to check arity or return shape, this test
     * fails — which is correct, because then the silent degradation above is no longer
     * silent and this lane's premise needs re-examining.
     */
    public function test_the_constructor_guard_checks_names_only(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/src/Teleframe/Store/TelegramStoreChannel.php');

        self::assertStringContainsString(
            'store channel api map is missing callable(s): ',
            $source,
            'the name guard must still exist',
        );
        self::assertStringNotContainsString(
            'is_callable',
            $source,
            'If the constructor starts validating callables or arities, the silent degradation this '
            .'lane documents is no longer silent, and this test must be revisited.',
        );
    }

    /** @return array<string, mixed> */
    private function peer(): array
    {
        return ['_' => 'inputPeerChannel', 'channel_id' => 1, 'access_hash' => 2];
    }
}
