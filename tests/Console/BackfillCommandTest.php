<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
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
    public function test_requires_account_and_peer(): void
    {
        $this->artisan('teleframe:backfill')
            ->expectsOutput('Both --account=<id> and --peer=<peer> are required.')
            ->assertExitCode(1);
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

        self::assertSame(1, DB::table('tl_message_message')->where('account_id', 7)->count(), 'message anchor landed');
        self::assertSame(1, DB::table('tl_message_message')->where('tl_id', 30)->count(), 'message instance landed');
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
        $this->bindScope(new ScriptedHistoryScope(['chat' => new RuntimeException('no history for you')]));

        $this->artisan('teleframe:backfill', ['--account' => 7, '--peer' => ['chat'], '--budget' => 10])
            ->expectsOutput('chat {messages:0, requests:5, offset_id:0, status:dead}')
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
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        self::assertArrayHasKey('teleframe:backfill', $kernel->all());
    }

    private function bindScope(UserAccountScope $scope): void
    {
        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
    }
}

/**
 * Canned messages.getHistory scope: a script of pages per peer (DESC
 * order like the wire); a Throwable step is thrown. Records calls.
 */
final class ScriptedHistoryScope extends FakeUserScope
{
    /** @var list<array{int, int}> */
    public array $historyCalls = [];

    private int $cursor = 0;

    /** @param array<string, list<array<string, mixed>|Throwable>> $script */
    public function __construct(private readonly array $script)
    {
        parent::__construct();
    }

    public function call(string $method, array $params = []): array
    {
        if ($method !== 'messages.getHistory') {
            return ['_' => 'rpc_result', 'method' => $method, 'params' => $params];
        }

        $this->historyCalls[] = [(int) ($params['offset_id'] ?? 0), (int) ($params['limit'] ?? 0)];

        $rawPeer = $params['peer'] ?? '';
        $peer = is_array($rawPeer) ? (string) ($rawPeer['_'] ?? 'peer') : (string) $rawPeer;

        $step = $this->script[$peer][$this->cursor]
            ?? throw new RuntimeException('ScriptedHistoryScope script exhausted for ' . $peer);
        $this->cursor++;

        if ($step instanceof Throwable) {
            throw $step;
        }

        return ['_' => 'messages.messagesSlice', 'count' => 1000, 'messages' => $step];
    }
}
