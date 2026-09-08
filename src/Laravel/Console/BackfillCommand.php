<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backfill\FetchQueue;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use RuntimeException;

/**
 * artisan teleframe:backfill (plan Phase 3, Task 4): quota-sliced
 * history backfill for one account (--account) over a FetchQueue seeded
 * from --peer (repeatable), driven by the Task-5 BackfillWorker port.
 *
 * Each fetched page is stored through the P2 ingest surface as a
 * messages.messages payload under the account's tenancy — plain ingest,
 * deliberately NOT ingestResponse: backfill pages are one-shot history
 * fetches, and the route-dedup key would collapse distinct pages with
 * identical shapes.
 *
 * v1 is report-only: the queue is rebuilt per run and the per-peer
 * offset_id resume cursor is printed (not persisted) — durable cursors
 * and queue state land with the Phase 4 persistence story (docs/bus.md).
 *
 * Transports are container seams (mirrors IngestCommand): SCOPE_RESOLVER_KEY
 * binds callable(int $accountId): UserAccountScope, INGESTER_KEY binds
 * callable(int): callable(list<messages>): array — tests swap both.
 */
final class BackfillCommand extends Command
{
    /** Container seam: callable(int $accountId): UserAccountScope (provider binds the live default). */
    public const SCOPE_RESOLVER_KEY = 'teleclient.backfill.scope-resolver';

    /** Container seam: callable(int $accountId): callable(list<array<string, mixed>>): array<string, mixed>. */
    public const INGESTER_KEY = 'teleclient.backfill.ingester';

    protected $signature = 'teleframe:backfill
        {--account= : Telegram account id (tenant) the history lands under}
        {--peer=* : Peer to backfill (repeatable: "@username", numeric id, "-100...")}
        {--budget=25 : Per-run request budget — the headroom rule halves it}';

    protected $description = 'Quota-aware history backfill into the P2 truth (fetch queue + offset cursor per peer)';

    protected $aliases = [];

    public function handle(): int
    {
        $accountId = (int) $this->option('account');

        $peers = array_values(array_filter(array_map(
            static fn ($peer): string => trim((string) $peer),
            (array) $this->option('peer'),
        )));

        if ($accountId <= 0 || $peers === []) {
            $this->error('Both --account=<id> and --peer=<peer> are required.');

            return self::FAILURE;
        }

        $queue = new FetchQueue();
        foreach ($peers as $peer) {
            $queue->push($peer);
        }

        $worker = new BackfillWorker(
            max(1, (int) ($this->option('budget') ?: $this->configInt('request_budget', 25))),
            max(1, $this->configInt('flood_cap_seconds', 3600)),
        );

        $scope = $this->resolveScope()($accountId);
        $ingester = $this->resolveIngester()($accountId);

        $messages = 0;
        $requests = 0;
        $done = 0;
        $dead = 0;
        $deadPeers = [];
        $status = 'empty';

        while (true) {
            $peer = $queue->pop();

            if ($peer === null) {
                break;
            }

            if (isset($deadPeers[$peer])) {
                $queue->complete($peer); // FetchQueue::fail re-queued it; dead peers are future-run material only

                continue;
            }

            $result = $worker->process(
                $peer,
                fn (int $offsetId, int $limit): array => $this->fetchPage($scope, $peer, $offsetId, $limit),
                static function (array $batch) use ($ingester): void {
                    $ingester($batch); // the default writer reports per batch; the worker only needs delivery
                },
            );

            $messages += $result['messages'];
            $requests += $result['requests'];
            $status = $result['status'];

            $this->line(sprintf(
                '%s {messages:%d, requests:%d, offset_id:%d, status:%s}',
                $peer,
                $result['messages'],
                $result['requests'],
                $result['offset_id'],
                $result['status'],
            ));

            if ($result['status'] === 'done') {
                $queue->complete($peer);
                $done++;

                continue;
            }

            if ($result['status'] === 'dead') {
                // attempts++ for the cross-run ledger — dead-lettered after
                // 5 runs (FetchQueue::MAX_ATTEMPTS); this run moves on.
                $queue->fail($peer, (string) $result['reason']);
                $deadPeers[$peer] = true;
                $dead++;

                continue;
            }

            $queue->requeue($peer);

            break; // budget: the peer stays queued at the front; the next run resumes from the reported offset_id
        }

        $this->info(sprintf(
            'backfill account=%d {messages:%d, requests:%d, status:%s} — %d done, %d dead, %d queued',
            $accountId,
            $messages,
            $requests,
            $status,
            $done,
            $dead,
            $queue->counts()['pending'],
        ));

        return self::SUCCESS;
    }

    /**
     * One messages.getHistory page (DESC); the BackfillWorker script owns
     * offset/limit sequencing.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchPage(UserAccountScope $scope, string $peer, int $offsetId, int $limit): array
    {
        $response = $scope->getHistory($peer, $limit, $offsetId);

        /** @var list<array<string, mixed>> $messages */
        $messages = $response['messages'] ?? [];

        return $messages;
    }

    /**
     * @return callable(int): UserAccountScope
     */
    private function resolveScope(): callable
    {
        $resolver = $this->getLaravel()->make(self::SCOPE_RESOLVER_KEY);

        if (! is_callable($resolver) || is_string($resolver)) {
            throw new RuntimeException(self::SCOPE_RESOLVER_KEY . ' must bind a callable(int): UserAccountScope');
        }

        return $resolver(...);
    }

    /**
     * @return callable(int): (callable(list<array<string, mixed>>): array<string, mixed>)
     */
    private function resolveIngester(): callable
    {
        $factory = $this->getLaravel()->make(self::INGESTER_KEY);

        if (! is_callable($factory) || is_string($factory)) {
            throw new RuntimeException(self::INGESTER_KEY . ' must bind a callable(int): callable');
        }

        return $factory(...);
    }

    private function configInt(string $key, int $fallback): int
    {
        $value = $this->getLaravel()->make('config')->get('teleframe.backfill.' . $key);

        return is_numeric($value) ? (int) $value : $fallback;
    }
}
