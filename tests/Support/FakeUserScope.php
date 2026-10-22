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
 *
 * ## THE OFFLINE ENVELOPE IS NOW THE REAL ONE (2026-10-18)
 *
 * This double used to answer every non-`updates.getDifference` call with
 *
 *     ['_' => 'rpc_result', 'method' => $method, 'params' => $params]
 *
 * ⛔ **WHICH WAS AN INCOMPLETE COPY, NOT AN INVENTION — and getting that wrong is the
 * lesson.** The real `Client::call()` in offline mode (`Client.php:335-342`) returns
 *
 *     ['_' => 'rpc_result', '_offline' => true, 'method' => …, 'params' => …, 'dc_id' => …]
 *
 * so `rpc_result` itself is genuine. A first pass at this lane asserted the whole wrapper
 * was a fabricated shape and would have "fixed" 25 working test files' worth of correct
 * assertions. What was actually missing was the two keys this copy dropped.
 *
 * ⛔ **`_offline` IS THE ONE THAT MATTERS.** It is the marker saying *"this answer was
 * fabricated locally; nothing was sent to Telegram"*. `ClientOfflineContractTest.php:52`
 * already guards the live counterpart — a real answer must NOT carry it — so without it
 * here, a test through this double cannot assert the property the offline contract exists
 * to express: that a fabricated result is labelled as fabricated.
 *
 * **Tests that WANT a specific value for a method should script it**; this class is for
 * methods whose answer is irrelevant, and saying so is more useful than returning a shape
 * that looks like a real answer.
 *
 * @see tests/Support/FakeUserScopeDifferentialTest.php
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
            // The REAL offline envelope, key for key (Client.php:335-342). `_offline`
            // is the marker that this was fabricated locally; `dc_id` names the DC it
            // is claimed to have come from.
            return [
                '_' => 'rpc_result',
                '_offline' => true,
                'method' => $method,
                'params' => $params,
                'dc_id' => 2,
            ];
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
