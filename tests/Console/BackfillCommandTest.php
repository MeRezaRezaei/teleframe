<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Backfill\CursorStoreContract;
use MeRezaRezaei\Teleframe\Backfill\EloquentCursorStore;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use RuntimeException;
use Throwable;

/**
 * teleframe:backfill (plan Phase 3, Task 4): FetchQueue + Task-5
 * BackfillWorker driven end-to-end against canned messages.getHistory
 * pages. The default batch ingester writes into the migrated sqlite
 * truth; the scope is swapped through the command's container seam —
 * no network anywhere.
 */
final class BackfillCommandTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: `grep -oE "account_id' => [0-9]+" tests/Console/
     * BackfillCommandTest.php | sort -u` yields exactly `7` and `99`, and the
     * fixture inserts raw rows under both.
     *
     * These were MISSING, and with foreign-key enforcement on (19 constraints,
     * `foreign_key_checks=1`) every insert was refused:
     *
     *   SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY
     *   constraint failed … insert into "tf_backfill_cursors"
     *   ("account_id", …) values (7, chat, 30, 0, complete, , …)
     *
     * which is 21 errors and 11 failures across this file in CI shard-2.
     *
     * The fix belongs HERE and not in `seedIngestTenant`, because that helper's
     * contract is deliberate: "each test declares the tenants it means to use,
     * so a test that writes under an unplanted id fails loudly instead of being
     * rescued." This test was writing under two unplanted ids — the loud failure
     * was the mechanism working, not a defect in it.
     *
     * Note the two id columns are NOT the same: `seedIngestTenant` matches on
     * `user_id`, while the tenant foreign key is `account_id → telegram_accounts
     * .id`. Both 7 and 99 are therefore declared, so both `user_id` and the
     * autoincrement `id` side of the reference exist.
     */
    protected function ingestTenantIds(): array
    {
        return [7, 99];
    }

    public function test_requires_account_and_peer(): void
    {
        $this->artisan('teleframe:backfill')
            ->expectsOutput('Both --account=<id> and --peer=<peer> are required (or pass --full/--discover).')
            ->assertExitCode(1);
    }

    /**
     * RENAMED DELIBERATELY, 2026-10-02. This was
     * `test_discover_only_enumerates_dialogs_without_writing`, and the name
     * asserted a contract the code never had: `--discover` DID write — no, it
     * did not write anything AT ALL, which was the defect. The page carried
     * every peer this account can reach plus their `access_hash`, and the sink
     * printed the peers and dropped the hashes, leaving `tf_users_access_hash`
     * empty and every numeric `--peer` refused with
     * `peer_without_access_hash` by the very run that had just held the answer
     * in memory.
     *
     * The two assertions below were never wrong and are now the whole of what
     * `--discover` still promises: it writes NO MESSAGES and it CRAWLS NO
     * HISTORY. What it additionally writes is the page's peer facts and their
     * hashes, at no extra request — see
     * `BackfillDiscoveryPersistTest` for the rows and the values.
     *
     * So the old title was wrong twice over: it promised "no writes" (false,
     * and the falseness was the bug), and renaming it is what stops the next
     * reader from "restoring" the read-only behaviour as if it were a
     * regression.
     */
    public function test_discover_only_enumerates_dialogs_and_stores_no_history(): void
    {
        $scope = new ScriptedHistoryScope([]);
        $scope->dialogPages = [[
            '_' => 'messages.dialogsSlice',
            'count' => 1,
            'dialogs' => [
                ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 20, 'date' => 9],
            ],
            'chats' => [],
            'users' => [['_' => 'user', 'id' => 7, 'access_hash' => '777']],
        ]];
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--discover' => true])
            ->expectsOutput('discovered 1 dialog(s) for account=7')
            ->assertExitCode(0);

        self::assertSame(0, DB::table('tf_messages')->where('account_id', 7)->count(), '--discover never writes a MESSAGE');
        self::assertSame([], $scope->historyCalls, '--discover never crawls history');
    }

    public function test_full_backfill_prints_per_peer_and_total_lines(): void
    {
        $this->bindScope(new ScriptedHistoryScope([
            'chat' => [
                [['id' => 30, '_' => 'message', 'message' => 'oldest', 'date' => 1_700_000_030, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 5]]],
                [],
            ],
        ]));

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 10])
            ->expectsOutput('chat {messages:1, requests:2, offset_id:30, status:done}')
            ->expectsOutput('backfill account=7 {messages:1, requests:2, status:done} — 1 done, 0 dead, 0 queued')
            ->assertExitCode(0);
    }

    public function test_fetched_pages_ingest_into_the_truth(): void
    {
        $this->bindScope(new ScriptedHistoryScope([
            'chat' => [
                [['id' => 30, '_' => 'message', 'message' => 'oldest', 'date' => 1_700_000_030, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 5]]],
                [],
            ],
        ]));

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat']])->assertExitCode(0);

        self::assertSame(1, DB::table('tf_messages')->where('account_id', 7)->count(), 'message row landed');
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 7)->where('id', 30)->count(), 'message id landed');
    }

    public function test_budget_stop_leaves_the_peer_queued_for_the_next_run(): void
    {
        $pages = [];

        for ($i = 0; $i < 12; $i++) {
            $pages[] = [['id' => 900 - $i, '_' => 'message', 'message' => "m$i", 'date' => 1_700_000_000, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 5]]];
        }

        $this->bindScope(new ScriptedHistoryScope(['chat' => $pages]));

        // budget 6 → headroom rule halves it to 3 requests: the 12 pages never finish
        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 6])
            ->expectsOutput('backfill account=7 {messages:3, requests:3, status:budget} — 0 done, 0 dead, 1 queued')
            ->assertExitCode(0);
    }

    public function test_permanent_fetch_failure_reports_dead(): void
    {
        // FIVE explicit throws, one per attempt. The old script was a single
        // Throwable in place of the per-peer step LIST, so `$script[$peer][0]`
        // raised `Error: Cannot use object of type RuntimeException as array`
        // — the test was passing on an accident, and its claimed reason
        // ("no history for you") never reached the worker even once.
        $alwaysFails = array_fill(0, 5, new RuntimeException('no history for you'));
        $this->bindScope(new ScriptedHistoryScope(['chat' => $alwaysFails]));

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 10])
            // The full line, deliberately: the reason is now PART of the report,
            // because `status:dead` with no reason is what made the live run
            // undiagnosable — five identical `fetch_failed` lines for a peer
            // Telegram could not resolve.
            ->expectsOutput('chat {messages:0, requests:5, offset_id:0, status:dead, reason:fetch_failed: RuntimeException: no history for you}')
            ->expectsOutput('backfill account=7 {messages:0, requests:5, status:dead} — 0 done, 1 dead, 0 queued')
            ->assertExitCode(0);
    }

    public function test_flood_wait_is_slept_through_by_the_worker(): void
    {
        $this->bindScope(new ScriptedHistoryScope([
            'chat' => [
                new FloodWaitException(0, 'FLOOD_WAIT_0', 420), // zero-second flood: sleep(0), budget ticks
                [['id' => 5, '_' => 'message', 'message' => 'm5', 'date' => 1_700_000_005, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 5]]],
                [],
            ],
        ]));

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 8])
            ->expectsOutput('chat {messages:1, requests:3, offset_id:5, status:done}')
            ->assertExitCode(0);
    }

    public function test_command_is_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);

        self::assertArrayHasKey('teleframe:backfill', $kernel->all());
    }

    public function test_state_prints_updates_state(): void
    {
        $scope = new ScriptedHistoryScope(['chat' => [[], []]]);
        $scope->state = ['_' => 'updates.state', 'pts' => 4242, 'qts' => 3, 'date' => 1_700_000_000, 'seq' => 7];
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--state' => true, '--budget' => 10])
            ->expectsOutput('state {pts:4242, qts:3, date:1700000000, seq:7}')
            ->assertExitCode(0);
    }

    public function test_full_discovers_every_dialog_and_backfills_each(): void
    {
        $scope = new ScriptedHistoryScope([
            'inputPeerChannel' => [
                [['id' => 10, '_' => 'message', 'message' => 'chan', 'date' => 1, 'out' => false, 'peer_id' => ['_' => 'peerChannel', 'channel_id' => 100]]],
                [],
            ],
            'inputPeerUser' => [
                [['id' => 20, '_' => 'message', 'message' => 'dm', 'date' => 2, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 7]]],
                [],
            ],
        ]);
        $scope->dialogPages = [[
            '_' => 'messages.dialogsSlice',
            'count' => 2,
            'dialogs' => [
                ['_' => 'dialog', 'peer' => ['_' => 'peerChannel', 'channel_id' => 100], 'top_message' => 10, 'date' => 10],
                ['_' => 'dialog', 'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 20, 'date' => 9],
            ],
            'chats' => [['_' => 'channel', 'id' => 100, 'access_hash' => '555']],
            'users' => [['_' => 'user', 'id' => 7, 'access_hash' => '777']],
        ]];
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--full' => true, '--budget' => 10])
            ->expectsOutput('discovered 2 dialog(s) for account=7')
            ->assertExitCode(0);

        self::assertSame(2, DB::table('tf_messages')->where('account_id', 7)->count());
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 7)->where('id', 10)->where('peer_id', 100)->count());
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 7)->where('id', 20)->where('peer_id', 7)->count());
    }

    public function test_full_resumes_a_budget_stopped_peer_until_the_queue_drains(): void
    {
        // Five single-message pages then exhaustion; budget 6 halves to 3
        // requests per pass, so the peer needs two passes to finish.
        $pages = [];
        for ($i = 0; $i < 5; $i++) {
            $pages[] = [['id' => 500 - $i, '_' => 'message', 'message' => "m$i", 'date' => 1_700_000_000, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 7]]];
        }
        $pages[] = [];

        $scope = new ScriptedHistoryScope(['chat' => $pages]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--full' => true, '--budget' => 6])
            ->expectsOutput('backfill account=7 {messages:5, requests:6, status:done} — 1 done, 0 dead, 0 queued')
            ->assertExitCode(0);

        self::assertSame(5, DB::table('tf_messages')->where('account_id', 7)->count());
    }

    /**
     * Cycle 17: a budget-stopped run must PERSIST its cursor, and the next
     * invocation must resume from the stored offset — never re-fetch the first
     * page. Before the cursor ledger, run 2 issued getHistory at offset 0 and
     * re-crawled the whole history from the top, so the crawl could never
     * reach message id 1 across a throttled sequence.
     *
     * Uses DEFAULT (non-full) mode, where a budget stop genuinely ends the
     * run — which is the real-world case the ledger exists for, and the case
     * docs/bus.md previously told operators to handle by hand.
     */
    public function test_second_invocation_resumes_from_the_persisted_offset(): void
    {
        $pages = [
            [['id' => 100, '_' => 'message', 'message' => 'a', 'date' => 1_700_000_000, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 7]]],
            [['id' => 90, '_' => 'message', 'message' => 'b', 'date' => 1_700_000_001, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 7]]],
            [],
        ];
        $scope = new ScriptedHistoryScope(['chat' => $pages]);
        $this->bindScope($scope);

        // Run 1: budget 2 halves to a single fetch, then the run stops at budget.
        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 2])
            ->assertExitCode(0);

        $persisted = (int) DB::table('tf_backfill_cursors')->where('account_id', 7)->value('offset_id');
        self::assertSame(100, $persisted, 'run 1 must persist the oldest id it fetched');
        self::assertSame(
            'active',
            (string) DB::table('tf_backfill_cursors')->where('account_id', 7)->value('status'),
            'a budget stop leaves the peer active, not terminal',
        );

        // Run 2: a fresh scope and a fresh invocation over the same account.
        $scope2 = new ScriptedHistoryScope(['chat' => $pages]);
        $this->bindScope($scope2);
        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 10])
            ->assertExitCode(0);

        self::assertNotEmpty($scope2->historyCalls, 'run 2 must have issued a getHistory call');
        // THE PROOF: run 2's first request carried the stored cursor, not 0.
        self::assertSame(100, $scope2->historyCalls[0][0], 'run 2 must resume at the persisted offset, never 0');
    }

    /**
     * A peer whose ledger says COMPLETE is never silently re-crawled. This is
     * the difference between a durable ledger and a fresh crawl every run.
     */
    public function test_a_completed_peer_is_not_recrawled_on_the_next_run(): void
    {
        $pages = [
            [['id' => 100, '_' => 'message', 'message' => 'a', 'date' => 1_700_000_000, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 7]]],
            [],
        ];
        $scope = new ScriptedHistoryScope(['chat' => $pages]);
        $this->bindScope($scope);
        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--full' => true, '--budget' => 10])
            ->assertExitCode(0);

        self::assertSame(1, DB::table('tf_backfill_cursors')->where('account_id', 7)->where('status', 'complete')->count());

        // Second run: the scope records zero history calls because the peer is
        // already terminal in the ledger.
        $scope2 = new ScriptedHistoryScope(['chat' => $pages]);
        $this->bindScope($scope2);
        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--full' => true, '--budget' => 10])
            ->assertExitCode(0);

        self::assertSame([], $scope2->historyCalls, 'a completed peer must not be re-crawled');
    }

    /**
     * Cycle 17: the "5 attempts across runs" contract in docs/bus.md is now
     * real. Before the ledger, each invocation started every peer at 0
     * attempts, so a peer that failed once per run could NEVER reach the
     * dead-letter threshold — it was reset before it could count.
     */
    public function test_attempts_accumulate_across_invocations_until_dead_lettered(): void
    {
        // A scope whose every getHistory throws: each run records one failure.
        $failing = ['chat' => [new FloodWaitException(0, 'FLOOD_WAIT_0', 420)]];
        $scope = new ScriptedHistoryScope($failing);
        $this->bindScope($scope);

        // One failing run ⇒ attempts 1, still active.
        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 50])
            ->assertExitCode(0);
        self::assertSame(1, (int) DB::table('tf_backfill_cursors')->where('account_id', 7)->value('attempts'));

        // Feed the ledger four more failures across further runs; the fifth
        // must dead-letter rather than resetting.
        for ($run = 2; $run <= 5; $run++) {
            $s = new ScriptedHistoryScope(['chat' => [new FloodWaitException(0, 'FLOOD_WAIT_0', 420)]]);
            $this->bindScope($s);
            $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 50])
                ->assertExitCode(0);
        }

        self::assertSame(
            'dead',
            (string) DB::table('tf_backfill_cursors')->where('account_id', 7)->value('status'),
            'a peer that failed across runs must eventually be dead-lettered',
        );
    }

    // ---------------------------------------------------------------------
    // The acceptance blocker: a bare `--peer=<id>` must never reach the wire.
    // ---------------------------------------------------------------------

    /**
     * THE HAPPY PATH. The account met that channel and dialog discovery stored
     * the whole peer — the dialog (`tf_dialogs`, `peer_type = 3`), the chat
     * fact, and the channel's flag-gated `access_hash`. The run must put the
     * RESOLVED peer on the wire — and the crawl must actually land a row, which
     * is the operator's criterion.
     *
     * THE FIXTURE USED TO LIE, and the lie is the defect. It seeded
     * `tf_chats_access_hash` with a NEGATIVE id and nothing else, which the
     * lookup's `$peerId < 0` branch happily answered — a shape no shipped
     * write path can produce, because `DialogDiscovery` stores channels with
     * their POSITIVE MTProto ids (`discovered channel:1232026269 {3:1232026269,
     * …}`). So the fixture proved a resolution no real `--peer` could ever
     * reach, while the real one was refused. It is seeded here the way the
     * mirror actually writes it: positive id, `peer_type = 3`, `constructor =
     * 'channel'`.
     *
     * Identity comes from `tf_dialogs.peer_type`, never from the sign — see
     * `BackfillPeerIdentityTest` for the whole argument, including why a
     * wrong-peer answer costs a budgeted request.
     */
    public function test_a_numeric_peer_is_resolved_from_the_shipped_mirror_hash_before_the_wire(): void
    {
        DB::table('tf_dialogs')->insert([
            'account_id' => 7, 'peer_type' => 3, 'peer_id' => 1234567890,
            'constructor' => 'channel', 'top_message' => 12413,
        ]);
        // The channel is planted where the SHIPPED SCHEMA puts it, 2026-10-11: the
        // `Chat` union is split, `Naming::CTOR_DOMAIN_OVERRIDES`
        // (`src/Schema/Generator/Naming.php:131-133`) routes `channel` /
        // `channelForbidden` to `tf_channels`, and `access_hash` is a BASE column
        // there (`2026_09_14_200003_create_tf_channels_tables.php:56`) rather than
        // a 1:1 child row. This planted `tf_chats` + `tf_chats_access_hash`, the
        // pre-split layout the write path can no longer produce — so the test was
        // asserting the resolver reads a table nothing fills. Entity and hash are
        // ONE row now, which is also why there is no FK to satisfy here.
        DB::table('tf_channels')->insert([
            'account_id' => 7, 'id' => 1234567890, 'constructor' => 'channel',
            'access_hash' => 998877665544332211,
        ]);

        $scope = new ScriptedHistoryScope([
            'inputPeerChannel' => [
                [['id' => 12413, '_' => 'message', 'message' => 'chan', 'date' => 1_700_000_013, 'out' => false, 'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1234567890]]],
                [],
            ],
        ]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['1234567890'], '--budget' => 10])
            ->assertExitCode(0);

        self::assertSame(
            '998877665544332211',
            (string) ($scope->peersSeen[0]['access_hash'] ?? ''),
            'the peer that reached messages.getHistory must carry the access_hash the mirror already held',
        );
        self::assertSame(
            'inputPeerChannel',
            (string) ($scope->peersSeen[0]['_'] ?? ''),
            'the peer must be an inputPeerChannel, not the bare id',
        );
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', 7)->where('id', 12413)->count(),
            'the resolved peer must let the crawl land the row the operator is chasing',
        );
    }

    /**
     * THE REFUSAL. Same command line, but the account has never met that peer,
     * so no hash exists. The run must refuse BY NAME, exit non-zero, and spend
     * ZERO requests — `$scope->calls` is empty because the refusal happens
     * before `resolveScope()` even dials the account-profile mirror.
     *
     * Before this, the bare id went to the wire and the run burned five
     * attempts to learn something this process already knew.
     */
    public function test_a_numeric_peer_the_account_cannot_resolve_is_refused_before_any_request(): void
    {
        $scope = new ScriptedHistoryScope([]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['-1001234567890']])
            // ONE substring per write: Laravel's buffered-output mock consumes
            // one expectation per doWrite, so two expectations cannot both be
            // satisfied by the single error line. The actionable half of this
            // message (`--full`) is pinned in HistoryIngestContractTest, where
            // the message itself is produced.
            ->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);

        self::assertSame([], $scope->calls, 'a refused peer must cost zero requests — including the profile mirror');
        self::assertSame(0, DB::table('tf_messages')->where('account_id', 7)->count());
    }

    /**
     * A BASIC GROUP needs no access_hash by design — `inputPeerChat` is the
     * wire shape, not a gap — so refusing it would be a false refusal. A
     * `tf_chats` row is what makes a positive id resolvable as one.
     */
    public function test_a_basic_group_peer_resolves_to_input_peer_chat_without_a_hash(): void
    {
        DB::table('tf_chats')->insert([
            'account_id' => 7, 'id' => 4242, 'constructor' => 'chat',
        ]);

        $scope = new ScriptedHistoryScope([
            'inputPeerChat' => [
                [['id' => 9, '_' => 'message', 'message' => 'group', 'date' => 1_700_000_009, 'out' => false, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 4242]]],
                [],
            ],
        ]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['4242'], '--budget' => 10])
            ->assertExitCode(0);

        self::assertSame('inputPeerChat', (string) ($scope->peersSeen[0]['_'] ?? ''));
        self::assertArrayNotHasKey('access_hash', $scope->peersSeen[0] ?? [], 'inputPeerChat carries no hash on the wire — that is the correct shape');
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 7)->where('id', 9)->count());
    }

    /**
     * TENANCY. An access_hash is per-account state. A hash another tenant
     * holds is not evidence THIS account can resolve the peer, and reading
     * across the tenancy would be reading another account's data.
     */
    public function test_a_hash_known_only_to_another_account_is_still_refused(): void
    {
        // Planted in the table the resolver now READS, 2026-10-11: `tf_channels`, with
        // `access_hash` inline as a base column
        // (`2026_09_14_200003_create_tf_channels_tables.php:56`, and
        // `Naming::CTOR_DOMAIN_OVERRIDES` at
        // `src/Schema/Generator/Naming.php:131-133` for the ctor routing).
        //
        // It used to plant `tf_chats` + `tf_chats_access_hash`, the pre-split
        // layout. That version still passed — but only ACCIDENTALLY, and for the
        // wrong reason: after the resolver moved to `tf_channels`, the hash
        // existed nowhere the lookup reads, so account 7 was refused because
        // NOTHING was found rather than because the row belonged to another
        // tenant. A cross-tenant test must have the row IN REACH and refuse it on
        // account scope alone, or it cannot tell tenancy apart from absence.
        // Planting the real table restores that: the hash is present, readable
        // under the right account_id, and account 7 must still refuse it.
        DB::table('tf_channels')->insert([
            'account_id' => 99, 'id' => -1001234567890, 'constructor' => 'channel',
            'access_hash' => 998877665544332211,
        ]);

        // The premise, asserted rather than assumed: the row really is stored,
        // and really is out of this account's reach.
        self::assertSame(
            '998877665544332211',
            (string) DB::table('tf_channels')->where('account_id', 99)->where('id', -1001234567890)->value('access_hash'),
            'precondition: account 99 holds a real, usable channel hash, so the refusal below can only be '
            .'about tenancy — a hash that does not exist anywhere would prove nothing about scope',
        );

        $scope = new ScriptedHistoryScope([]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['-1001234567890']])
            ->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);

        self::assertSame([], $scope->calls, 'another account\'s hash must not unlock this account\'s crawl');
    }

    /**
     * A row that EXISTS with a zero hash means "Telegram has not told us",
     * which is worse than no row at all — and it must NOT fall through to the
     * basic-group branch, because `inputPeerChat` would name a different peer.
     */
    public function test_a_user_row_with_a_zero_hash_is_refused_rather_than_treated_as_a_group(): void
    {
        // `tf_users_access_hash (account_id, id) → tf_users` is a composite foreign
        // key added 2026-10-04, so the USER must exist before its hash can. The
        // fixture planted `tf_chats` for this id but never `tf_users`, and the
        // insert was refused outright:
        //   19 FOREIGN KEY constraint failed … insert into "tf_users_access_hash"
        // Planting the user keeps the test's meaning intact — the assertion is that
        // a ZERO hash on a row that EXISTS is refused, which is strictly stronger
        // than a hash whose user is missing entirely.
        DB::table('tf_users')->insert(['account_id' => 7, 'id' => 555, 'constructor' => 'user']);
        DB::table('tf_users_access_hash')->insert(['account_id' => 7, 'id' => 555, 'access_hash' => 0]);
        DB::table('tf_chats')->insert(['account_id' => 7, 'id' => 555, 'constructor' => 'chat']);

        $scope = new ScriptedHistoryScope([]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['555']])
            ->expectsOutputToContain(BackfillCommand::PEER_UNRESOLVED)
            ->assertExitCode(1);

        self::assertSame([], $scope->calls);
    }

    /**
     * ADDITIVE. A non-numeric `--peer` (a label, a `@username`) is untouched
     * by the new resolution step — it is neither refused nor rewritten, and
     * in particular it is not turned into an `inputPeerChat` it never was.
     */
    public function test_a_non_numeric_peer_argument_is_passed_through_unchanged(): void
    {
        $scope = new ScriptedHistoryScope([
            'chat' => [
                [['id' => 30, '_' => 'message', 'message' => 'm30', 'date' => 1_700_000_030, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 5]]],
                [],
            ],
        ]);
        $this->bindScope($scope);

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 10])
            ->assertExitCode(0);

        self::assertSame('chat', (string) ($scope->peersSeen[0] ?? ''), 'a non-numeric peer must reach the scope byte-identical');
        self::assertSame(1, DB::table('tf_messages')->where('account_id', 7)->where('id', 30)->count());
    }

    // ---------------------------------------------------------------------
    // A fetched page that was never written must not be a quiet success.
    // ---------------------------------------------------------------------

    /**
     * THE DISCARDED RETURN. The default writer's report used to be thrown
     * away at `$ingester($batch);`, so a batch that stored NOTHING still
     * produced a clean exit 0 and a `messages:N` summary — which is exactly
     * how `SELECT COUNT(*) FROM tf_messages` could stay 0 with no error
     * anywhere. A reported failure must now surface AND fail the run, while
     * the fetch loop itself still survives (the writer must not kill it).
     */
    public function test_a_batch_the_writer_refused_is_surfaced_and_fails_the_run(): void
    {
        $scope = new ScriptedHistoryScope([
            'chat' => [
                [['id' => 31, '_' => 'message', 'message' => 'm31', 'date' => 1_700_000_031, 'out' => false, 'peer_id' => ['_' => 'peerUser', 'user_id' => 5]]],
                [],
            ],
        ]);
        $this->bindScope($scope);
        $this->app->bind(
            BackfillCommand::INGESTER_KEY,
            static fn (): callable => static fn (int $accountId): callable => static fn (array $batch): array => [
                'stored' => 0,
                'root' => null,
                'reason' => 'RuntimeException: the writer refused this batch',
            ],
        );

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 10])
            // One substring spanning both halves of the single error line: the
            // count of unwritten batches AND the writer's own reason, which is
            // the part the old `$ingester($batch);` threw away.
            ->expectsOutputToContain('1 batch(es) were NOT stored — 1 message(s): RuntimeException: the writer refused this batch')
            ->assertExitCode(1);

        self::assertNotSame([], $scope->historyCalls, 'a failing writer must NOT kill the fetch loop');
        self::assertSame(0, DB::table('tf_messages')->where('account_id', 7)->count());
    }

    /**
     * The DEFAULT writer must publish a `reason` key at all — `null` on
     * success. Its absence is what let the command discard the return with
     * nothing to report, so the key's existence is the load-bearing part.
     */
    public function test_the_default_writer_publishes_a_reason_key(): void
    {
        $ingester = $this->app->make(BackfillCommand::INGESTER_KEY);
        self::assertIsCallable($ingester);

        $report = $ingester(7)([[
            'id' => 77, '_' => 'message', 'message' => 'm77', 'date' => 1_700_000_077, 'out' => false,
            'peer_id' => ['_' => 'peerUser', 'user_id' => 5],
        ]]);

        self::assertIsArray($report);
        self::assertArrayHasKey('reason', $report, 'the writer must always publish a reason field the command can read');
        self::assertNull($report['reason'], 'a successful batch has no reason');
        self::assertSame(1, $report['stored']);
    }

    private function bindScope(UserAccountScope $scope): void
    {
        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
        // Cycle 17: the real Eloquent cursor ledger against the migrated
        // sqlite, so resume is exercised through the actual store.
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): CursorStoreContract => new EloquentCursorStore,
        );
    }
}

/**
 * Canned messages.getHistory / messages.getDialogs scope: a script of pages
 * per peer (DESC order like the wire); a Throwable step is thrown. Records
 * calls. Dialog pages are served in order; history is keyed by the InputPeer
 * constructor (inputPeerUser / inputPeerChat / inputPeerChannel).
 */
final class ScriptedHistoryScope extends FakeUserScope
{
    /** @var list<array<int, int>> */
    public array $historyCalls = [];

    /**
     * @var list<mixed> the `peer` param exactly as it arrived on the wire —
     *                  whatever the command handed to getHistory, unmodified. This is
     *                  the only place the access_hash resolution is observable.
     */
    public array $peersSeen = [];

    /** @var array<string, mixed> the updates.state answer */
    public array $state = ['_' => 'updates.state', 'pts' => 0, 'qts' => 0, 'date' => 0, 'seq' => 0];

    /** @var list<array<string, mixed>> dialogs responses, in order */
    public array $dialogPages = [];

    /** @var list<array<string, mixed>> recorded getDialogs params */
    public array $dialogsCalls = [];

    private int $dialogCursor = 0;

    /** @var array<string, int> per-peer script cursor */
    private array $cursors = [];

    /** @param array<string, list<array<string, mixed>|Throwable>> $script */
    public function __construct(private readonly array $script)
    {
        parent::__construct();
    }

    public function call(string $method, array $params = []): array
    {
        // Record EVERY dialled method, exactly as FakeUserScope does. Without
        // this, $scope->calls is always empty for this double — which made
        // "a refused peer spends zero requests" pass VACUOUSLY.
        $this->calls[] = ['method' => $method, 'params' => $params];

        return match ($method) {
            'messages.getHistory' => $this->history($params),
            'messages.getDialogs' => $this->dialogs($params),
            'updates.getState' => $this->state,
            default => ['_' => 'rpc_result', 'method' => $method, 'params' => $params],
        };
    }

    /** @param array<string, mixed> $params */
    private function history(array $params): array
    {
        $this->historyCalls[] = [(int) ($params['offset_id'] ?? 0), (int) ($params['limit'] ?? 0)];

        $rawPeer = $params['peer'] ?? '';
        $this->peersSeen[] = $rawPeer;
        $peer = is_array($rawPeer) ? (string) ($rawPeer['_'] ?? 'peer') : (string) $rawPeer;

        $step = $this->script[$peer][$this->cursors[$peer] ?? 0]
            ?? throw new RuntimeException('ScriptedHistoryScope script exhausted for '.$peer);
        $this->cursors[$peer] = ($this->cursors[$peer] ?? 0) + 1;

        if ($step instanceof Throwable) {
            throw $step;
        }

        return ['_' => 'messages.messagesSlice', 'count' => 1000, 'messages' => $step];
    }

    /** @param array<string, mixed> $params */
    private function dialogs(array $params): array
    {
        $this->dialogsCalls[] = $params;

        if (! isset($this->dialogPages[$this->dialogCursor])) {
            return ['_' => 'messages.dialogs', 'dialogs' => [], 'users' => [], 'chats' => []];
        }

        return $this->dialogPages[$this->dialogCursor++];
    }
}
