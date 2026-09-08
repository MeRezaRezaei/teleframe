<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage;

use Psr\SimpleCache\CacheInterface;

/**
 * Per-account stage state (Phase 5e, Q17). Purely PLAIN ARRAYS — never
 * serialized objects — persisted through any PSR-16 store, so a restart
 * over a filesystem/redis store resumes the exact flow with zero loss.
 *
 * State shape (the Q17 contract):
 *
 *     [
 *         'stageSet'      => 'onboarding',
 *         'currentStage'  => 'profile',
 *         'data'          => ['email' => 'a@b.c'],
 *         'msgIds'        => [12, 13],
 *     ]
 *
 * State lives under the deterministic key `teleframe.stage.state:{accountId}`
 * so supervisor restarts (`transition` key or not) recover the same mailbox
 * thread. `advance()` stores validated values, walks to the next stage, and
 * saves in ONE write.
 */
final class StageState
{
    public const KEY_PREFIX = 'teleframe.stage.state.';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly StageRegistry $registry,
    ) {
    }

    public static function key(string $accountId): string
    {
        return self::KEY_PREFIX . $accountId;
    }

    /**
     * Begin a flow for $accountId. Returns the new state so the caller can
     * immediately read `currentStage` / `data` without a second read.
     *
     * @return array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>}
     */
    public function start(StageSet $set, string $accountId): array
    {
        $state = $this->blank($set->name());
        $this->cache->set(self::key($accountId), $state);

        return $state;
    }

    /**
     * Record validated field values and transition to the next incomplete
     * stage. `$accountId === ''` is a valid "anonymous" flow key.
     *
     * @param array<string, mixed> $values
     *
     * @return array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>}
     */
    public function advance(string $accountId, array $values, ?StageSet $hint = null): array
    {
        $state = $this->current($accountId) ?? $this->blank();

        foreach ($values as $key => $value) {
            $state['data'][$key] = $value;
        }

        $set = $hint ?? $this->registry->setFor($state['stageSet']);
        if ($set !== null) {
            $nextStage = $set->pendingField((string) $state['currentStage'], $state['data']);
            $state['currentStage'] = $nextStage['complete'] ? null : $nextStage['stage'];
        }

        $this->save($accountId, $state);

        return $state;
    }

    /**
     * The current state array, or null when no flow is active for the account.
     *
     * @return array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>}|null
     */
    public function current(string $accountId): ?array
    {
        $state = $this->cache->get(self::key($accountId));

        return is_array($state) ? $this->normalize($state) : null;
    }

    /** Alias for current(): true when the account has an active flow. */
    public function has(string $accountId): bool
    {
        return $this->current($accountId) !== null;
    }

    /**
     * Record message ids issued during this flow (e.g. the prompt the bot
     * sent while the stage waits). Purely additive bookkeeping; the flow logic
     * never walks it.
     */
    public function touch(string $accountId, int|string ...$msgIds): void
    {
        $state = $this->current($accountId);
        if ($state === null) {
            return;
        }
        foreach ($msgIds as $id) {
            if (! in_array((int) $id, $state['msgIds'], true)) {
                $state['msgIds'][] = (int) $id;
            }
        }
        $this->save($accountId, $state);
    }

    /** Drop all state for the account (flow completed or abandoned). */
    public function finish(string $accountId): void
    {
        $this->cache->delete(self::key($accountId));
    }

    /**
     * @return array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>}
     */
    private function blank(string $stageSet = ''): array
    {
        return [
            'stageSet' => $stageSet,
            'currentStage' => null,
            'data' => [],
            'msgIds' => [],
        ];
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>}
     */
    private function normalize(array $state): array
    {
        $data = isset($state['data']) && is_array($state['data']) ? $state['data'] : [];
        $msgIds = isset($state['msgIds']) && is_array($state['msgIds'])
            ? array_values(array_map('intval', $state['msgIds']))
            : [];

        return [
            'stageSet' => isset($state['stageSet']) && is_string($state['stageSet']) ? $state['stageSet'] : '',
            'currentStage' => isset($state['currentStage']) && is_string($state['currentStage']) ? $state['currentStage'] : null,
            'data' => $data,
            'msgIds' => $msgIds,
        ];
    }

    /** @param array{stageSet: string, currentStage: ?string, data: array<string, mixed>, msgIds: list<int>} $state */
    private function save(string $accountId, array $state): void
    {
        $this->cache->set(self::key($accountId), $state);
    }
}