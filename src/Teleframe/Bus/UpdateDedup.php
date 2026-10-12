<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

/**
 * Redis hash-based dedup for update payloads on the ingest path.
 * Prevents duplicate processing when Telegram retransmits updates
 * (pts retransmit, gap recovery) or the Redis stream redelivers
 * on consumer crash.
 *
 * Uses one hash per account (tg:dedup:{accountId}) with the update
 * hash as the field — avoids needing SET/EXISTS on the contract.
 * Hash expires after 1 hour: enough to cover gap-recovery windows,
 * short enough to avoid unbounded Redis memory growth.
 */
final class UpdateDedup
{
    private const TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly RedisConnectionContract $redis,
    ) {}

    /**
     * Has this update hash been seen for this account?
     */
    public function seen(int $accountId, string $updateHash): bool
    {
        return $this->redis->hget(self::key($accountId), $updateHash) !== null;
    }

    /**
     * Mark an update hash as seen for this account. Sets a TTL to
     * avoid unbounded growth.
     */
    public function mark(int $accountId, string $updateHash): void
    {
        $key = self::key($accountId);
        $this->redis->hset($key, $updateHash, '1');
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    private static function key(int $accountId): string
    {
        return "tg:dedup:{$accountId}";
    }
}
