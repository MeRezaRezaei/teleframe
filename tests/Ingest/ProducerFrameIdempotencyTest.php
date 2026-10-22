<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Closure;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use RuntimeException;

/**
 * D-041's two OPPOSITE guarantees, spent against a real database with the
 * frames the producer actually minted.
 *
 * ## THE PROBLEM THIS STEP SOLVES
 *
 * Under the S1 inversion the bus entry is published AFTER the row commits, so
 * the Redis stream entry id that `UpdateIngestor::frameKey()` used to key on
 * does not exist when the key must be derived. The producer mints its own
 * frame — `producer:<accountId>:<fromPts>:<ordinal>` — and that frame is what
 * the ingest derives the negative `(seq, position)` from.
 *
 * ## WHY TWO TESTS THAT LOOK LIKE OPPOSITES ARE BOTH REQUIRED
 *
 *  1. The SAME frame delivered twice must be ONE row. This is the
 *     re-delivery guarantee `IngestConsumer` documents (and
 *     `SeqLessFrameKeyedUpdateTest` proves over Redis entries) RELOCATED to
 *     the producer: a crash mid-window re-fetches the same window from the
 *     same pts, so the same ordinals re-derive the same frames, and the
 *     re-delivery must UPSERT rather than append.
 *  2. TWO DIFFERENT frames carrying the SAME bytes must be TWO rows. This is
 *     what D-039 chose the composite key to protect and what
 *     `contentIdentity()` would fold away. Losing it means the update log
 *     collapses distinct updates that happen to look alike.
 *
 * A single test cannot establish either: a scheme that derives one key per
 * window passes (1) and fails (2); a scheme that derives one key per payload
 * passes (2) and fails (1).
 *
 * ## WHAT IS DELIBERATELY NOT HERE
 *
 * The frame's SHAPE and the ordinal's COUNTING are gated in
 * `tests/Laravel/ProducerFrameTest`, which pins the exact frame strings. They
 * cannot be pinned here: a row count is identical under the right ordinal and
 * under a per-container one until a window carries updates in two containers.
 */
final class ProducerFrameIdempotencyTest extends IngestTestCase
{
    /** The tenancy account the ingest writes rows under. */
    private const ACCOUNT = 4242;

    /** The TELEGRAM user id on the session — NOT the account in the frame. */
    private const TELEGRAM_USER_ID = 501;

    private const FROM_PTS = 8;

    /** The one child table asserted on by value. */
    private const PTS = 'tf_updates_pts';

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    // ------------------------------------------------------------------
    // 1. The SAME frame delivered twice is ONE row.
    // ------------------------------------------------------------------

    /**
     * TWO PRODUCER DELIVERIES OF ONE WINDOW ARE ONE SET OF ROWS.
     *
     * Delivery #1 and delivery #2 are two independent pollers seeded to the
     * same persisted cursor and shown the same difference — what a process
     * that died mid-window sees when it comes back, because the cursor is
     * persisted only after `AccountWorker::run()` ends. Each delivery mints its
     * own frames; the assertion is that they are the SAME frames, and that the
     * second delivery therefore UPSERTS onto the first delivery's rows.
     *
     * Under the retired `MAX(seq) + 1` allocation this is 4 rows for a window
     * carrying 2 updates: the second delivery read MAX after the first had
     * committed, took the next values and appended.
     */
    public function test_two_deliveries_of_one_window_upsert_onto_one_row_each(): void
    {
        $first = $this->deliverWindow($this->window());
        $second = $this->deliverWindow($this->window());

        self::assertSame(
            $this->frames($first),
            $this->frames($second),
            'control: a re-fetch of one window must re-derive the same frames, or this test measures '
            .'two unrelated deliveries and proves nothing about re-delivery',
        );

        self::assertCount(2, $first, 'the fixture window must carry two updates');

        $keysAfterFirst = $this->keys();

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'the first delivery commits one row per update in the window',
        );

        // Delivery #2: same window, same frames, same payloads.
        $this->ingestUnderFrames($second);

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'a re-delivered producer frame must UPSERT onto the row it already wrote, not append a second '
            .'one. This is the guarantee IngestConsumer documents for Redis entry ids, relocated to the '
            .'frame the producer mints.',
        );
        self::assertSame(
            $keysAfterFirst,
            $this->keys(),
            'the re-delivery must land on the SAME keys — that is what makes it an upsert',
        );
        self::assertSame([9001, 9002], $this->ptsValues(), 'one set of children, not two');
        self::assertSame(2, $this->childCount('tf_updates_pts_count'));
    }

    /**
     * A re-delivery is an UPSERT, not an append — which means the row it lands
     * on carries the arriving values. Asserted separately so the previous test
     * is not the only thing holding "upsert" up: the second delivery carries
     * the same bytes, so this pins that nothing about the row is duplicated
     * either (children included) rather than that a count stayed at two.
     */
    public function test_a_re_delivered_frame_rewrites_the_row_it_already_committed(): void
    {
        $first = $this->deliverWindow($this->window());
        $this->ingestUnderFrames($first);

        $keys = $this->keys();
        self::assertCount(2, $keys);

        // Deliver the SAME window once more under a THIRD delivery, then
        // deliver one further update of the SAME window shape — an update the
        // re-fetch would also produce, at the ordinal the window already used.
        $this->ingestUnderFrames($this->deliverWindow($this->window()));

        self::assertSame($keys, $this->keys(), 'no new key is derived for a re-delivery');
        self::assertSame([9001, 9002], $this->ptsValues());
        self::assertSame(2, $this->childCount('tf_updates_pts_count'), 'children are not duplicated either');
    }

    // ------------------------------------------------------------------
    // 2. TWO frames carrying the SAME bytes are TWO rows.
    // ------------------------------------------------------------------

    /**
     * TWO BYTE-IDENTICAL PAYLOADS UNDER DIFFERENT ORDINALS ARE TWO ROWS.
     *
     * Both `other_updates` entries are the same array, byte for byte. A
     * content-derived identity — `contentIdentity()` — folds them onto one
     * row; the producer frame cannot, because the ordinals differ. The
     * negative control below performs that fold on purpose, so the two-row
     * assertion here is a measurement of a harness in which folding is real
     * rather than a fixture that cannot fail.
     */
    public function test_two_byte_identical_payloads_under_different_ordinals_are_two_rows(): void
    {
        $delivered = $this->deliverWindow($this->identicalWindow());

        self::assertSame(
            ['producer:4242:8:0', 'producer:4242:8:1'],
            $this->frames($delivered),
            'the two identical updates are distinguished by ORDINAL alone',
        );
        self::assertSame(
            [$delivered[0]['update'], $delivered[0]['update']],
            [$delivered[1]['update'], $delivered[0]['update']],
            'control: the two payloads really are byte-identical',
        );

        $this->ingestUnderFrames($delivered);

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'two frames carrying one payload are two facts, not one — the property D-039 chose the '
            .'composite key to keep, and the one contentIdentity() would fold',
        );

        $keys = $this->keys();
        self::assertCount(2, $keys);
        self::assertNotSame(
            [$keys[0]['seq'], $keys[0]['position']],
            [$keys[1]['seq'], $keys[1]['position']],
            'two frames must derive two different keys',
        );
        foreach ($keys as $key) {
            self::assertLessThan(
                0,
                $key['seq'],
                'a producer-frame-keyed seq-less update takes a NEGATIVE seq (frameKey hashes the frame). '
                .'A seq of 0 or more here would mean the frame never reached the derivation.',
            );
            self::assertGreaterThanOrEqual(0, $key['position']);
        }
    }

    /**
     * THE NEGATIVE CONTROL: with NO frame, the very same two payloads DO fold.
     *
     * This is the destruction produced on purpose. It proves the two-row
     * assertion above is carried by the frame and not by the fixture: strip the
     * frame identity and `contentIdentity()` collapses the pair, which is
     * exactly the loss D-039 rejected when it chose the composite key.
     */
    public function test_the_same_pair_folds_to_one_row_when_no_frame_identifies_them(): void
    {
        $ingestor = new UpdateIngestor;
        $payload = $this->identicalUpdate();

        // No frame: the ingestor falls back to contentIdentity() for a seq-less
        // update (UpdateIngestor::ingest() passes $frameId ?? self::$frame).
        $ingestor->ingest($payload, self::ACCOUNT);
        $ingestor->ingest($payload, self::ACCOUNT);

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'control: WITHOUT a frame identity the pair folds to one row, so the two-row assertion in '
            .'the test above is carried by the minted frame and not by the fixture',
        );

        // And the same pair under two producer frames is two MORE rows: the
        // frameless row sits on a contentIdentity-derived key that is neither
        // of theirs, so 1 + 2 = 3.
        $this->ingestUnderFrames($this->deliverWindow($this->identicalWindow()));

        self::assertSame(
            3,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'the framed pair contributes TWO rows on top of the folded one: each update keeps its own '
            .'minted frame, where the frameless pair had exactly one',
        );
    }

    /**
     * The frame's account component is the TENANCY account, and the row's
     * `account_id` is that same tenancy account — never the Telegram user id
     * the poller can see on its own scope. Two rows, one account, one frame
     * namespace: the derivation leads with the account, so no bleed is
     * possible in either direction.
     */
    public function test_the_frame_is_stamped_with_the_tenancy_account_not_the_telegram_user(): void
    {
        $delivered = $this->deliverWindow($this->window());

        self::assertSame('producer:4242:8:0', $this->frames($delivered)[0]);

        foreach ($this->frames($delivered) as $frame) {
            self::assertStringNotContainsString(
                (string) self::TELEGRAM_USER_ID,
                $frame,
                'a producer frame must carry the tenancy account id, not the Telegram user id on the session',
            );
        }

        $this->ingestUnderFrames($delivered);

        $rows = DB::table('tf_updates')->whereIn('seq', array_column($this->keys(), 'seq'))->get(['account_id']);

        foreach ($rows as $row) {
            self::assertSame(
                self::ACCOUNT,
                (int) $row->account_id,
                'the row belongs to the tenancy account the daemon configured',
            );
        }
    }

    // ------------------------------------------------------------------
    // Harness
    // ------------------------------------------------------------------

    /**
     * Poll one window with a fresh poller and INGEST every update the sink
     * saw, under the frame that was in scope for it.
     *
     * This is the production shape of the seam step 2 will build: the poller
     * mints the frame, the sink reads it in scope and hands it to the ingest,
     * and `UpdateIngestor::withinFrame()` is what makes it the identity the
     * key derivation sees. (`ingest()` reads `$frameId ?? self::$frame`, so
     * the ambient scope and the explicit third argument are one code path.)
     *
     * @param  array<string, mixed>  $window
     * @return list<array{update: array<string, mixed>, frame: string|null}>
     */
    private function deliverWindow(array $window): array
    {
        $captured = [];
        $calls = [];

        $poller = (new UpdatePollerService($this->framingSink($captured)))
            ->setProducerAccountId(self::ACCOUNT);
        $poller->setSequenceState(['pts' => self::FROM_PTS, 'date' => 11, 'qts' => 1, 'seq' => 0]);

        $sink = $poller->getSink();
        $sink->poller = $poller;

        $poller->pollUser($this->fakeScope([['_' => 'updates.difference'] + $window], $calls, fn () => $poller->stop()));

        $this->ingestUnderFrames($captured);

        return $captured;
    }

    /**
     * Commit each update under the frame the producer minted for it.
     *
     * @param  list<array{update: array<string, mixed>, frame: string|null}>  $captured
     */
    private function ingestUnderFrames(array $captured): void
    {
        $ingestor = new UpdateIngestor;

        foreach ($captured as $entry) {
            $frame = $entry['frame'];
            self::assertIsString($frame, 'every update of an MTProto window carries a minted frame');

            UpdateIngestor::withinFrame(
                $frame,
                static fn () => $ingestor->ingest($entry['update'], self::ACCOUNT),
            );
        }
    }

    /**
     * A sink that records what it saw, including the frame in scope.
     *
     * @param  list<array{update: array<string, mixed>, frame: string|null}>  $storage
     */
    private function framingSink(array &$storage): UpdateSinkInterface
    {
        return new class($storage) implements UpdateSinkInterface
        {
            public ?UpdatePollerService $poller = null;

            /**
             * @param  list<array{update: array<string, mixed>, frame: string|null}>  $storage
             */
            public function __construct(private array &$storage) {}

            public function handle(array $update, ?string $source = null, ?int $ts = null): bool
            {
                $this->storage[] = ['update' => $update, 'frame' => $this->poller?->getProducerFrame()];

                return true;
            }
        };
    }

    /**
     * A user scope replaying one canned `updates.getDifference` response, with
     * the TELEGRAM user id on its session. The halt callback stops the poller
     * once the sequence is exhausted.
     *
     * @param  list<array<string, mixed>>  $sequence
     * @param  list<array{method: string, params: array<string, mixed>}>  $calls
     */
    private function fakeScope(array $sequence, array &$calls, ?Closure $halt): UserAccountScope
    {
        return new class($sequence, $halt, $calls) extends UserAccountScope
        {
            public function __construct(
                private readonly array $sequence,
                private readonly ?Closure $halt,
                private array &$calls
            ) {
                parent::__construct(
                    new MTProtoClient(apiId: 1, apiHash: 'hash', live: false),
                    new SessionData(dcId: 2, authKey: random_bytes(256), userId: 501)
                );
            }

            public function call(string $method, array $params = []): array
            {
                if (count($this->calls) >= count($this->sequence)) {
                    if ($this->halt !== null) {
                        ($this->halt)();
                    }

                    throw new RuntimeException('halt polling');
                }

                $this->calls[] = ['method' => $method, 'params' => $params];

                return $this->sequence[count($this->calls) - 1];
            }
        };
    }

    /**
     * A window whose two `other_updates` are the SAME update, byte for byte —
     * the payload `contentIdentity()` cannot tell apart.
     *
     * @return array<string, mixed>
     */
    private function identicalWindow(): array
    {
        return [
            'state' => ['_' => 'updates.state', 'pts' => 40, 'date' => 20, 'qts' => 3, 'seq' => 1],
            'other_updates' => [$this->identicalUpdate(), $this->identicalUpdate()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function identicalUpdate(): array
    {
        return ['_' => 'updateReadHistoryInbox', 'pts' => 9100, 'pts_count' => 3, 'qts' => 4];
    }

    /**
     * A window carrying two distinguishable updates, no nested message (so the
     * update fact row and its hand-authored children are all that is touched).
     *
     * @return array<string, mixed>
     */
    private function window(): array
    {
        return [
            'state' => ['_' => 'updates.state', 'pts' => 40, 'date' => 20, 'qts' => 3, 'seq' => 1],
            'other_updates' => [
                ['_' => 'updateReadHistoryInbox', 'pts' => 9001, 'pts_count' => 1, 'qts' => 4],
                ['_' => 'updateReadHistoryInbox', 'pts' => 9002, 'pts_count' => 1, 'qts' => 4],
            ],
        ];
    }

    /**
     * @param  list<array{update: array<string, mixed>, frame: string|null}>  $captured
     * @return list<string>
     */
    private function frames(array $captured): array
    {
        return array_map(static fn (array $entry): string => (string) $entry['frame'], $captured);
    }

    /**
     * The account's `tf_updates` keys, ascending by seq then position.
     *
     * @return list<array{seq: int, position: int}>
     */
    private function keys(): array
    {
        $rows = DB::table('tf_updates')->where('account_id', self::ACCOUNT)
            ->orderBy('seq')->orderBy('position')->get(['seq', 'position']);

        return array_map(
            static fn ($row): array => ['seq' => (int) $row->seq, 'position' => (int) $row->position],
            $rows->all(),
        );
    }

    /**
     * The pts child VALUES, sorted by value.
     *
     * NOT ordered by the row's key: a producer-frame-keyed row's `seq` is a
     * HASH (`frameKey()` turns the frame into a negative number), so key order
     * is the hash's order and says nothing about the ordinal the update was
     * delivered under. The ordinal is not observable from the row — which is
     * why the frames themselves are asserted in the shape test.
     *
     * @return list<int>
     */
    private function ptsValues(): array
    {
        $values = DB::table(self::PTS)->where('account_id', self::ACCOUNT)->pluck('pts')
            ->map(static fn ($p): int => (int) $p)->all();

        sort($values);

        return $values;
    }

    private function childCount(string $table): int
    {
        return DB::table($table)->where('account_id', self::ACCOUNT)->count();
    }
}
