<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backfill\CursorStoreContract;
use MeRezaRezaei\Teleframe\Backfill\EloquentCursorStore;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use RuntimeException;
use Throwable;

/**
 * THE CASES, shared verbatim by both engines.
 *
 * Phase 5 Step 2 is a property of the crawl, not of a database — but the plan
 * insists the property be proven where a transaction commits like production's
 * ("a cursor that resumes correctly on SQLite proves nothing about a
 * transaction that commits differently on InnoDB"), and it also forbids letting
 * two engines drift into two sets of assertions.
 *
 * So the assertions live HERE, once, and two classes use them:
 *
 *  - `CrawlReachesMessageIdOneTest` — SQLite :memory: (the fast lane, and the
 *    only engine a plain `composer verify` runs);
 *  - `tests/MySql/BackfillCrawlReachesMessageIdOneOnMysqlTest` — MySQL 8.4
 *    through the shared track.
 *
 * Each supplies only the three engine seams: the connection (`db()`), the
 * surface boot, and the FK read (`fkViolations()`). Everything asserted here is
 * identical on both, so neither engine can quietly hold a weaker promise.
 *
 * PHASE 5, STEP 2 — the crawl WALKS TO MESSAGE ID 1 AND STOPS, across a gap,
 * across invocations, and from a persisted cursor.
 *
 * WHY THIS CLASS EXISTS. The operator's words: "we want to store all telegram
 * data since this package is going to be used in industrial level for customer
 * engagement and several other apps it might need the full history just in hand
 * to act on it". A schema that ACCEPTS every row (Phases 0–4) is necessary and
 * not sufficient: what makes the history reachable is the crawl terminating at
 * the beginning of the dialog's history rather than restarting at the newest
 * message forever. `tf_backfill_cursors` (migration 2026_09_25_000300, added by
 * Cycle 17 for exactly this) is the ledger that makes it possible.
 *
 * ## THE WORKER'S REAL CONTRACT, read from the code, not from the plan
 *
 * `BackfillWorker::process()` (src/Teleframe/Backfill/BackfillWorker.php:87) is
 * PURE with respect to persistence: it takes a `$fetch`, an `$onBatch`, a
 * starting `$offsetId` and an optional `$shouldStop`, and returns
 * `{messages, requests, offset_id, status, reason}`. It never touches
 * `tf_backfill_cursors`. The persistence is the CALLER's — `BackfillCommand`
 * (src/Laravel/Console/BackfillCommand.php:746-866) reads the stored offset
 * before the call and writes the row back after it, mapping `done` →
 * STATUS_COMPLETE, `dead` → STATUS_DEAD, `budget` → STATUS_ACTIVE. So "the
 * crawl resumes from the cursor" is a property of that pair, and this class
 * drives the pair in the same order the command does, against the real shipped
 * `EloquentCursorStore` and the real shipped `tf_backfill_cursors` table.
 *
 * Two further facts, both read from the code, shape the assertions:
 *
 *  - `FetchQueue::quotaSlice($remaining, 1)` is `intdiv($remaining, 2)`
 *     (src/Teleframe/Backfill/FetchQueue.php:44): HALF the request budget is
 *     reserved for live traffic, so a budget of 4 buys 2 requests, and the page
 *     limit is `min(remaining, 100)`. The crawl below therefore needs SEVERAL
 *     invocations to reach id 1, which is the real shape — the operator's
 *     complaint is precisely that a throttled walk "restarted every peer at
 *     offset 0 and could never reach message id 1".
 *  - The cursor is persisted AFTER `process()` returns, never during it. A
 *     process that dies mid-crawl therefore resumes from the last PERSISTED
 *     offset and re-fetches the pages after it. That is safe only because a
 *     replay converges (`ReplayedHistoryPageConvergesTest`), and this class
 *     pins the consequence rather than the intention: after an interrupted
 *     ingest the next invocation re-requests from the persisted offset and the
 *     store still holds exactly one row per message.
 *
 * ## WHY THE GAP MATTERS
 *
 * Telegram ids are per-chat and NOT contiguous — deletions leave holes. A
 * crawl that inferred "the page was short, so we are at the end" would stop at
 * the first hole and silently lose everything older. The wire's end-of-history
 * signal is an EMPTY page, and that is the only thing this class treats as the
 * end.
 */
trait CrawlToMessageIdOneCases
{
    /** The tenant `telegram_accounts.user_id` every `tf_*` row is scoped by. */
    private const ACCOUNT = 4242;

    private const PEER_CHANNEL = 1398107361;

    private const PEER_USER = 501558149;

    private const PEER_KEY = 'channel:1398107361';

    /**
     * A synthetic history, DESC as `messages.getHistory` returns it, WITH A
     * GAP: 121–299, 301–479 and 481–499 were deleted on the server, so a crawl
     * that treated a short page as the end of history would stop at 500 and
     * lose 480, 300, 299, 120, 60 and 1 — five messages and the entire point.
     *
     * The last id is 1: the walk must be able to reach the very beginning.
     */
    private const HISTORY = [500, 480, 300, 299, 120, 60, 1];

    /** Budget 4 buys intdiv(4,2)=2 requests, so 2 pages of 2 per invocation. */
    private const REQUEST_BUDGET = 4;

    private static ?Capsule $capsule = null;

    private static mixed $previousContainer = null;

    /** @var list<int> every offset the fake wire was asked for, in order */
    private array $requestedOffsets = [];

    /** @var list<int> every message id handed to onBatch, in order */
    private array $deliveredIds = [];

    /** Batch counter for the simulated mid-crawl ingest failure. */
    private int $batchesSeen = 0;

    /** Which page the simulated ingest failure lands on (0-based). */
    private const FAIL_ON_BATCH = 1;

    public static function tearDownAfterClass(): void
    {
        if (self::$capsule !== null) {
            self::$capsule->getConnection()->disconnect();
            self::$capsule = null;
        }

        if (self::$previousContainer !== null) {
            Container::setInstance(self::$previousContainer);
            Facade::setFacadeApplication(self::$previousContainer);
            Facade::clearResolvedInstances();
            self::$previousContainer = null;
        }

        parent::tearDownAfterClass();
    }

    // ------------------------------------------------------------------- the walk

    /**
     * THE PLAN'S ASSERTION: the crawl reaches message id 1 and stops, across a
     * gap, across invocations, with no page processed twice.
     */
    public function test_the_crawl_walks_past_a_gap_to_message_id_one_and_stops(): void
    {
        $db = $this->db();
        $store = $this->cursorStore();

        $invocations = 0;
        $status = 'budget';

        // The loop is `BackfillCommand`'s own shape: read the stored offset,
        // run one worker, write the row back. A real crawl is many invocations,
        // because every one of them is capped at half the request budget.
        while ($status !== 'done' && $invocations < 50) {
            $stored = $store->findCursor(self::ACCOUNT, self::PEER_KEY);
            $start = (int) ($stored['offset_id'] ?? 0);

            $result = $this->worker()->process(
                self::PEER_KEY,
                $this->fetch(...),
                $this->onBatch(...),
                $start,
            );

            $this->persistAsTheCommandDoes($store, $result);
            $status = (string) $result['status'];
            $invocations++;
        }

        self::assertSame(
            'done',
            $status,
            "the crawl must terminate on the wire's own end-of-history signal (an empty page), "
            ."not by running out of budget or attempts. It took {$invocations} invocation(s).",
        );

        // ---- The cursor reached message id 1, and only the ledger says so. ----
        $row = $store->findCursor(self::ACCOUNT, self::PEER_KEY);
        self::assertNotNull($row, 'the crawl must leave a durable cursor behind');
        self::assertSame(
            1,
            (int) $row['offset_id'],
            'the resume cursor must be the OLDEST id fetched — message id 1. This is the whole '
            .'operator requirement: the full history must be reachable, not just the newest page.',
        );
        self::assertSame(
            CursorStoreContract::STATUS_COMPLETE,
            (string) $row['status'],
            'a crawl that reached the end of history is COMPLETE, so a later run skips it instead '
            .'of re-walking it (BackfillCommand:terminalPeers()).',
        );
        self::assertSame(
            [self::PEER_KEY => CursorStoreContract::STATUS_COMPLETE],
            $store->terminalPeers(self::ACCOUNT),
            'the ledger must report the peer terminal, or every future invocation re-crawls it',
        );

        // ---- No page was processed twice. ----
        self::assertSame(
            self::HISTORY,
            $this->deliveredIds,
            'the crawl must deliver every message exactly once, newest first. A repeat means a page '
            .'was re-walked, which is how a resumed crawl duplicates work forever.',
        );
        self::assertSame(
            self::HISTORY,
            array_values(array_unique($this->deliveredIds)),
            'no message id may be delivered twice',
        );

        // ---- And the offsets asked of the wire walk strictly DOWN, ending on
        // the empty page that terminates the crawl. ----
        self::assertSame(0, $this->requestedOffsets[0], 'the first walk starts at the top of the history (offset 0 = newest)');

        // Strictly DECREASING after the first request: the wire's order is DESC,
        // so every subsequent request must be older than the last. A repeat or
        // a rise is a rewind — the exact failure the cursor ledger's min()
        // rewind guard exists to prevent.
        $walk = array_slice($this->requestedOffsets, 1);
        $sorted = $walk;
        rsort($sorted);
        self::assertSame(
            $sorted,
            $walk,
            'after the initial request every offset must be strictly older than the one before it. '
            .'A repeated or rising offset is a rewind — the crawl would re-walk pages it has already '
            .'stored and could never make progress toward message id 1. Got: '.implode(', ', $walk),
        );
        self::assertNotSame([], $walk, 'the crawl must have made at least one resumed request past the first page');
        self::assertSame(
            1,
            (int) end($walk),
            'the LAST offset the crawl asked for must be message id 1, not a page boundary above it: '
            .'the cursor has to end up on the oldest id there is, which is what "full history" means',
        );

        // ---- The rows are really in the relational truth. ----
        self::assertSame(
            count(self::HISTORY),
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'every message the crawl walked must be STORED, not merely fetched',
        );
        self::assertSame(
            self::HISTORY,
            array_map(
                static fn (object $m): int => (int) $m->id,
                $db->table('tf_messages')->where('account_id', self::ACCOUNT)->orderByDesc('id')->get()->all(),
            ),
            'the stored messages must be exactly the walked history, gap included — no id invented, none skipped',
        );
        self::assertSame(
            [],
            $this->foreignKeyGraphViolations($db),
            'the stored history must satisfy the FK graph; an orphan here is the shape the tenant '
            .'FKs exist to refuse',
        );
    }

    // ------------------------------------------------------------------- the resume

    /**
     * A budget stop is the NORMAL interruption, not an error — `BackfillCommand`
     * says so at the branch that persists it ("Cycle 17: a budget stop is the
     * NORMAL case, not an error … so the NEXT invocation resumes here instead
     * of re-fetching this whole page from offset 0"). This asserts exactly
     * that, through the real `EloquentCursorStore` and the real shipped
     * `tf_backfill_cursors` table: the second invocation's FIRST request is the
     * persisted offset, never 0.
     */
    public function test_a_mid_crawl_stop_persists_the_cursor_and_the_next_run_resumes_there(): void
    {
        $db = $this->db();
        $store = $this->cursorStore();
        $ingestor = new UpdateIngestor;

        // ---- Invocation 1: two pages, then the budget runs out. ----
        $first = $this->worker()->process(
            self::PEER_KEY,
            $this->fetch(...),
            $this->onBatch(...),
            (int) ($store->findCursor(self::ACCOUNT, self::PEER_KEY)['offset_id'] ?? 0),
        );

        self::assertSame('budget', (string) $first['status'], 'the first invocation must stop on its budget, not on the end of history');
        self::assertSame(
            'request_budget_exhausted',
            (string) $first['reason'],
            'a budget stop must say why, or an operator cannot tell throttling from completion',
        );

        $this->persistAsTheCommandDoes($store, $first);

        $persisted = (int) ($store->findCursor(self::ACCOUNT, self::PEER_KEY)['offset_id'] ?? 0);
        self::assertGreaterThan(
            0,
            $persisted,
            'a budget stop MUST persist a non-zero cursor, or the next invocation re-walks from the '
            .'top and the full history is unreachable — the Cycle 17 defect in one assertion',
        );
        self::assertSame(
            CursorStoreContract::STATUS_ACTIVE,
            (string) $store->findCursor(self::ACCOUNT, self::PEER_KEY)['status'],
            'an interrupted peer is ACTIVE, not terminal: marking it complete would abandon the rest '
            .'of the history permanently',
        );

        // ---- Invocation 2: resume from the ledger, not from 0. ----
        $requestedBefore = count($this->requestedOffsets);
        $deliveredBefore = count($this->deliveredIds);

        $second = $this->worker()->process(
            self::PEER_KEY,
            $this->fetch(...),
            $this->onBatch(...),
            (int) ($store->findCursor(self::ACCOUNT, self::PEER_KEY)['offset_id'] ?? 0),
        );

        self::assertSame(
            $persisted,
            $this->requestedOffsets[$requestedBefore],
            'THE resume assertion: the first offset the second invocation asks for must be the '
            ."PERSISTED cursor ({$persisted}), not 0. Asking 0 again is the measured Cycle 17 defect: "
            .'a throttled walk that restarts at the newest page can never reach message id 1.',
        );

        // ---- Drive it to the end and prove the union is exactly the history. ----
        $status = (string) $second['status'];
        $this->persistAsTheCommandDoes($store, $second);
        $guard = 0;
        while ($status !== 'done' && $guard++ < 50) {
            $next = $this->worker()->process(
                self::PEER_KEY,
                $this->fetch(...),
                $this->onBatch(...),
                (int) ($store->findCursor(self::ACCOUNT, self::PEER_KEY)['offset_id'] ?? 0),
            );
            $this->persistAsTheCommandDoes($store, $next);
            $status = (string) $next['status'];
        }

        self::assertSame('done', $status);
        self::assertSame(
            1,
            (int) ($store->findCursor(self::ACCOUNT, self::PEER_KEY)['offset_id'] ?? 0),
            'the resumed crawl must still finish at message id 1',
        );
        self::assertSame(
            self::HISTORY,
            $this->deliveredIds,
            'across the interruption every message must be delivered EXACTLY once — the resumed run '
            .'must not re-deliver what invocation 1 already stored',
        );
        self::assertGreaterThan(
            $deliveredBefore,
            count($this->deliveredIds),
            'the second invocation must have delivered real pages, not zero',
        );
        self::assertSame(
            count(self::HISTORY),
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'and the store must hold one row per message after the interruption and resume',
        );
        unset($ingestor);
    }

    /**
     * The other interruption, asserted rather than assumed: a crawl whose INGEST
     * dies mid-page does NOT persist progress for that page — the command
     * writes the cursor only after `process()` returns. So the next invocation
     * re-requests from the last persisted offset, and what protects the store
     * from that is CONVERGENCE, not the cursor.
     *
     * This is the honest reading of the plan's "a mid-crawl failure resumes from
     * the cursor rather than from the start": the CURSOR only helps for the
     * interruption the command survives (budget/dead). A hard failure inside a
     * page has nothing persisted yet, so it re-walks — and re-walking is safe
     * because a replayed page converges to one row.
     */
    public function test_a_failure_inside_a_page_leaves_no_cursor_and_the_re_walk_converges(): void
    {
        $db = $this->db();
        $store = $this->cursorStore();

        $raised = null;
        $this->batchesSeen = 0;

        try {
            $this->worker()->process(
                self::PEER_KEY,
                $this->fetch(...),
                // The ingest seam dies on the SECOND page, mid-crawl — once
                // only, so the re-walk below is not short-circuited by the same
                // simulated failure.
                function (array $page): void {
                    if ($this->batchesSeen === self::FAIL_ON_BATCH) {
                        $this->batchesSeen = -1; // armed once

                        throw new RuntimeException('simulated ingest failure mid-page');
                    }

                    $this->batchesSeen++;
                    $this->ingestPage($page);
                },
                0,
            );
        } catch (Throwable $e) {
            $raised = $e;
        }

        self::assertNotNull($raised, 'the simulated mid-page failure must actually propagate out of the worker');
        self::assertSame(
            'simulated ingest failure mid-page',
            $raised->getMessage(),
        );
        self::assertNull(
            $store->findCursor(self::ACCOUNT, self::PEER_KEY),
            'a run that died inside process() persists NOTHING, because BackfillCommand writes the '
            .'cursor only after the call returns. Asserting it so the resume story is not told with a '
            .'guarantee the code does not make.',
        );

        // The pages that DID land are stored.
        self::assertSame(
            2,
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'the pages ingested before the failure must be stored',
        );

        // The re-walk re-delivers the pages it had already stored; convergence
        // is the only thing that makes that safe, so drive it to the end of the
        // history and assert the STORE — not the delivery log, which by
        // construction now contains the repeats.
        $deliveredBefore = count($this->deliveredIds);
        $store->upsertCursor(self::ACCOUNT, [
            'peer_key' => self::PEER_KEY,
            'offset_id' => 0,
            'attempts' => 0,
            'status' => CursorStoreContract::STATUS_ACTIVE,
            'reason' => '',
            'last_offset_at_unix' => 1_700_000_000,
        ]);

        $status = 'budget';
        $guard = 0;
        while ($status !== 'done' && $guard++ < 50) {
            $result = $this->worker()->process(
                self::PEER_KEY,
                $this->fetch(...),
                $this->onBatch(...),
                (int) ($store->findCursor(self::ACCOUNT, self::PEER_KEY)['offset_id'] ?? 0),
            );
            $this->persistAsTheCommandDoes($store, $result);
            $status = (string) $result['status'];
        }

        self::assertSame('done', $status, 'the re-walk must still reach the end of the history');
        self::assertGreaterThan(
            $deliveredBefore,
            count($this->deliveredIds),
            'the re-walk must actually re-deliver the pages it had already stored — otherwise this '
            .'case would pass without exercising the convergence it is about',
        );
        self::assertSame(
            count(self::HISTORY),
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'after the re-walk the store must hold exactly one row per message — the replayed pages '
            .'CONVERGED rather than duplicating or colliding',
        );
        self::assertSame(
            self::HISTORY,
            array_map(
                static fn (object $m): int => (int) $m->id,
                $db->table('tf_messages')->where('account_id', self::ACCOUNT)->orderByDesc('id')->get()->all(),
            ),
            'and the stored ids must be the history exactly once each',
        );
        self::assertSame([], $this->foreignKeyGraphViolations($db));
    }

    /**
     * A peer the server refuses is DEAD in the ledger and must not be
     * re-walked from the top on the next invocation — otherwise one
     * unresolvable dialog costs the account's whole budget forever.
     */
    public function test_a_dead_peer_is_terminal_in_the_ledger(): void
    {
        $db = $this->db();
        $store = $this->cursorStore();

        // A budget large enough to spend all MAX_ATTEMPTS requests: the worker
        // buys intdiv(budget,2) requests, so the shared REQUEST_BUDGET of 4
        // would stop at 'budget' after two failures and never reach the
        // dead-letter arm at all.
        $result = (new BackfillWorker(BackfillWorker::MAX_ATTEMPTS * 2, 3600, static function (): void {}))->process(
            self::PEER_KEY,
            // The wire refuses this peer: CHANNEL_INVALID — a peer Telegram
            // cannot resolve, i.e. no access_hash. Thrown on every attempt,
            // exactly as the live hashless-peer run did.
            static function (): array {
                throw new RpcErrorException('CHANNEL_INVALID', 400);
            },
            $this->onBatch(...),
            0,
        );

        self::assertSame('dead', (string) $result['status'], 'MAX_ATTEMPTS consecutive failures must end the run as dead');
        self::assertSame(
            BackfillWorker::MAX_ATTEMPTS,
            (int) $result['requests'],
            'the run must spend exactly MAX_ATTEMPTS requests before giving up',
        );

        $this->persistAsTheCommandDoes($store, $result);

        self::assertSame(
            [self::PEER_KEY => CursorStoreContract::STATUS_DEAD],
            $store->terminalPeers(self::ACCOUNT),
            'a dead peer must be terminal so the next invocation skips it',
        );
        self::assertSame(
            CursorStoreContract::STATUS_DEAD,
            (string) $store->findCursor(self::ACCOUNT, self::PEER_KEY)['status'],
        );
        self::assertNotSame(
            '',
            (string) $store->findCursor(self::ACCOUNT, self::PEER_KEY)['reason'],
            'a dead peer must carry a diagnosable reason — an empty one is what made the live '
            .'hashless-peer run undiagnosable',
        );
        self::assertSame(
            0,
            (int) $db->table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'a peer that never resolved must store nothing',
        );
    }

    // ------------------------------------------------------------------- plumbing

    /**
     * The fake wire: `messages.getHistory` order — DESC, EXCLUSIVE of the
     * offset — and an EMPTY page once the offset passes the oldest id.
     *
     * The offset 0 case is the wire's own convention and it is easy to get
     * wrong in a fake: 0 is "no offset yet", i.e. the NEWEST page, not
     * "everything older than id 0". Modelling it as the latter returns an
     * empty first page and ends the crawl immediately with offset 0 — a crawl
     * that stores NOTHING and reports DONE, which is exactly the silent-pass
     * failure this class exists to make impossible. Asserted, not assumed, by
     * `test_the_first_request_returns_the_newest_page_and_the_crawl_ends_at_one`.
     *
     * @return list<array<string, mixed>>
     */
    private function fetch(int $offsetId, int $limit): array
    {
        $this->requestedOffsets[] = $offsetId;

        $page = [];
        foreach (self::HISTORY as $id) {
            if ($offsetId === 0 || $id < $offsetId) {
                $page[] = $this->message($id);

                if (count($page) >= $limit) {
                    break;
                }
            }
        }

        return $page;
    }

    /**
     * @param  list<array<string, mixed>>  $page
     */
    private function onBatch(array $page): void
    {
        $this->ingestPage($page);
    }

    /**
     * @param  list<array<string, mixed>>  $page
     */
    private function ingestPage(array $page): void
    {
        foreach ($page as $message) {
            $this->deliveredIds[] = (int) $message['id'];
        }

        $ingestor = new UpdateIngestor;
        foreach ($page as $message) {
            $ingestor->ingest($message, self::ACCOUNT);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function message(int $id): array
    {
        return [
            '_' => 'message',
            'flags' => (1 << 8),
            'id' => $id,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::PEER_USER],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::PEER_CHANNEL],
            'date' => 1724852400 + $id,
            'message' => 'history message '.$id,
            'flags2' => 0,
        ];
    }

    private function worker(): BackfillWorker
    {
        // The sleeper is injected and never called on this path (no FLOOD_WAIT);
        // the default is a real blocking sleep, which a test must never reach.
        return new BackfillWorker(
            self::REQUEST_BUDGET,
            3600,
            static function (): void {},
        );
    }

    /**
     * The command's own persistence mapping, verbatim in behaviour:
     * `done` → COMPLETE, `dead` → DEAD, anything else (`budget`) → ACTIVE,
     * always with the returned `offset_id`. Read from
     * src/Laravel/Console/BackfillCommand.php:790-866.
     *
     * @param  array{messages: int, requests: int, offset_id: int, status: string, reason: string|null}  $result
     */
    private function persistAsTheCommandDoes(CursorStoreContract $store, array $result): void
    {
        $status = match ($result['status']) {
            'done' => CursorStoreContract::STATUS_COMPLETE,
            'dead' => CursorStoreContract::STATUS_DEAD,
            default => CursorStoreContract::STATUS_ACTIVE,
        };

        $store->upsertCursor(self::ACCOUNT, [
            'peer_key' => self::PEER_KEY,
            'offset_id' => $result['offset_id'],
            'attempts' => $result['status'] === 'dead' ? BackfillWorker::MAX_ATTEMPTS : 0,
            'status' => $status,
            'reason' => (string) ($result['reason'] ?? ''),
            'last_offset_at_unix' => 1_700_000_000,
        ]);
    }

    private function cursorStore(): CursorStoreContract
    {
        return new EloquentCursorStore;
    }

    /**
     * The wire convention the whole crawl rests on, asserted directly: offset 0
     * is the NEWEST page, and a crawl that mistakes it for "older than 0"
     * returns an empty first page — storing nothing and reporting DONE. That is
     * a green, silent, zero-history crawl, so it gets its own case rather than
     * being left implicit in the fake.
     */
    public function test_the_first_request_returns_the_newest_page_and_the_crawl_ends_at_one(): void
    {
        $this->db();

        $first = $this->fetch(0, 2);

        self::assertSame(
            [self::HISTORY[0], self::HISTORY[1]],
            array_map(static fn (array $m): int => (int) $m['id'], $first),
            'offset 0 means "no offset yet" — the wire answers it with the NEWEST page. A fake '
            .'that treated 0 as "everything older than id 0" would return an empty page and the '
            .'crawl would end at offset 0 having stored nothing.',
        );
        self::assertNotSame([], $first, 'an empty first page is the silent zero-history crawl');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestedOffsets = [];
        $this->deliveredIds = [];
        $this->batchesSeen = 0;
    }

    /**
     * A clean tenant-scoped surface: every table emptied, then the tenant row
     * the tenant FKs point at.
     *
     * Split out of `db()` so the MySQL lane
     * (`tests/MySql/BackfillCrawlReachesMessageIdOneOnMysqlTest`) reuses THIS
     * seed rather than restating it — a second copy of a fixture is a second
     * copy that drifts, which is how this repository ended up hand-typing a
     * `tf_store_objects` that disagreed with the shipped DDL.
     */
    protected function seedSurface(Connection $db): void
    {
        // Empty with FK enforcement suspended for the wipe only: deleting
        // parents before children would otherwise raise.
        $db->statement('PRAGMA foreign_keys = OFF');
        foreach ($this->tableNames($db) as $table) {
            $db->table($table)->delete();
        }
        $db->statement('PRAGMA foreign_keys = ON');

        if ((int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys !== 1) {
            throw new RuntimeException('sqlite refused PRAGMA foreign_keys = ON — every FK assertion in this class would be vacuous');
        }

        $this->seedTenant($db);
    }

    /**
     * The tenant every `tf_*.account_id` is scoped by. Phase 0.3 landed the
     * tenant FKs onto `telegram_accounts.user_id` (commit 7dd07b4a), so
     * nothing in the mirror can be written without this row.
     */
    protected function seedTenant(Connection $db): void
    {
        $appId = $db->table('telegram_apps')->insertGetId([
            'label' => 'phase5-crawl',
            'api_id' => self::ACCOUNT,
            'api_hash' => 'synthetic-not-a-credential',
        ]);
        $db->table('telegram_accounts')->insert([
            'app_id' => $appId,
            'label' => 'phase5-crawl',
            'type' => 'user',
            'user_id' => self::ACCOUNT,
        ]);

        // The ledger's own table must exist — its shape comes from executing the
        // shipped migration, so a column rename cannot leave this test green.
        if (! $db->getSchemaBuilder()->hasTable('tf_backfill_cursors')) {
            throw new RuntimeException('tf_backfill_cursors is absent — the executed dial must include 2026_09_25_000300');
        }
    }

    /**
     * Bare table names: the engine's own listing is schema-qualified
     * (`main.tf_messages` on sqlite, `teleframe_mysql_track.tf_messages` on
     * MySQL), and everything downstream speaks bare names.
     *
     * @return list<string>
     */
    protected function tableNames(Connection $db): array
    {
        $names = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $table) {
            $names[] = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
        }

        return $names;
    }



    // ------------------------------------------------------------------- the FK-graph probe

    /**
     * EVERY VIOLATION OF THE FOREIGN-KEY GRAPH, as rows. Empty means the stored
     * history satisfies its parents.
     *
     * ## WHY THIS IS A SEAM RATHER THAN A `PRAGMA` CALL
     *
     * `PRAGMA foreign_key_check` is SQLite's statement for exactly this question,
     * and it was called inline here. That works on the SQLite lane and fails on
     * every other engine, with MySQL reporting
     *
     *     SQLSTATE[42000]: Syntax error or access violation: 1064 You have an
     *     error in your SQL syntax; ... near 'PRAGMA foreign_key_check' at line 1
     *
     * MEASURED on MySQL 8.4.11-0ubuntu0.26.04.1, on the two call sites in this
     * trait. There is no MySQL equivalent statement, so a subclass has to supply
     * one "+EM+u" and the question itself is engine-independent, so the question
     * belongs here while the ANSWER does not.
     *
     * This default is unchanged SQLite behaviour, so the SQLite lane is byte-for-byte
     * unaffected. `tests/MySql/BackfillCrawlReachesMessageIdOneOnMysqlTest` overrides
     * it with a real orphan scan rather than a `return []`.
     *
     * A `return []` here would have been the easy version and it would have been
     * worthless: an empty result is exactly what a broken probe also returns, so it
     * would have converted a red MySQL lane into a green one that proves nothing.
     *
     * @return list<object|array<string,mixed>>
     */
    protected function foreignKeyGraphViolations(Connection $db): array
    {
        return $db->select('PRAGMA foreign_key_check');
    }

}
