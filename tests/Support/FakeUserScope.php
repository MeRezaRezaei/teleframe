<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use Throwable;

/**
 * Offline UserAccountScope double for AccountWorker tests (plan Phase 3,
 * Task 4): its call() answers updates.getDifference from a scripted step
 * list (a page array to return, or a Throwable to throw) and otherwise
 * mirrors the live scope's contract. Running past the script stalls on
 * differenceEmpty so a missing stop would hang the test visibly.
 */
class FakeUserScope extends UserAccountScope
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    private int $cursor = 0;

    /**
     * @param list<array<string, mixed>|Throwable> $steps answers to successive
     *        updates.getDifference calls; running past the script throws
     */
    public function __construct(
        private readonly array $steps = [],
        private readonly int $userId = 42,
    ) {
        parent::__construct(
            new MTProtoClient(apiId: 1, apiHash: 'x', session: new SessionData(dcId: 2, authKey: 'k', userId: $userId)),
            new SessionData(dcId: 2, authKey: 'k', userId: $userId),
        );
    }

    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($method !== 'updates.getDifference') {
            return ['_' => 'rpc_result', 'method' => $method, 'params' => $params];
        }

        $step = $this->steps[$this->cursor] ?? null;
        $this->cursor++;

        if ($step instanceof Throwable) {
            throw $step;
        }

        if (is_array($step)) {
            return $step;
        }

        // Past the script: fail loudly (the poller propagates it, the
        // worker 3-strikes, the test fails) instead of hanging silently.
        throw new \RuntimeException('FakeUserScope script exhausted');
    }

    /** A differenceEmpty terminal response (calm-cadence: the poller sleeps 1s after it). */
    public static function emptyDifference(int $pts, int $date): array
    {
        return ['_' => 'updates.differenceEmpty', 'pts' => $pts, 'date' => $date, 'seq' => 0];
    }

    /** A differenceSlice carrying one update; advancing pts loops without sleeping. */
    public static function sliceWithUpdate(int $pts, string $constructor = 'updateNewMessage'): array
    {
        return [
            '_' => 'updates.differenceSlice',
            'new_messages' => [],
            'other_updates' => [['_' => $constructor, 'pts' => $pts]],
            'intermediate_state' => ['_' => 'updates.state', 'pts' => $pts, 'date' => 1_700_000_000 + $pts, 'qts' => 0, 'seq' => 0, 'unread_count' => 0],
        ];
    }

    public static function flood(int $seconds): FloodWaitException
    {
        return new FloodWaitException($seconds, "FLOOD_WAIT_$seconds", 420);
    }

    public static function dcMigration(int $dc): DcMigrationException
    {
        return new DcMigrationException($dc);
    }
}
