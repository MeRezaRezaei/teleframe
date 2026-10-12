<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;

/**
 * Redis-backed per-account pts watermark (TDLib §updates state persistence).
 *
 * Stores the four-valued sequence state {pts, date, qts, seq} and the
 * per-channel pts map so the poller can resume across restarts. Keys
 * expire after 30 days — a dead account's watermark naturally reclaims.
 */
final class PtsWatermark
{
    private const TTL_SECONDS = 30 * 24 * 3600; // 30 days

    public function __construct(
        private readonly RedisConnectionContract $redis,
    ) {}

    /**
     * Load the persisted sequence state for an account, or null on first poll.
     *
     * @return array{pts: int, date: int, qts: int, seq: int}|null
     */
    public function get(int $accountId): ?array
    {
        $raw = $this->redis->hgetall(self::stateKey($accountId));

        if ($raw === [] || !isset($raw['pts'])) {
            return null;
        }

        return [
            'pts'  => (int) $raw['pts'],
            'date' => (int) ($raw['date'] ?? 0),
            'qts'  => (int) ($raw['qts'] ?? 0),
            'seq'  => (int) ($raw['seq'] ?? 0),
        ];
    }

    /**
     * Persist the sequence state for an account.
     *
     * @param array{pts: int, date: int, qts: int, seq: int} $state
     */
    public function put(int $accountId, array $state): void
    {
        $key = self::stateKey($accountId);
        foreach ($state as $field => $value) {
            $this->redis->hset($key, $field, (string) $value);
        }
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Record a channel pts observation (monotonic — only advances forward).
     */
    public function touchChannel(int $accountId, int $channelId, int $pts): void
    {
        $key = self::channelKey($accountId);
        $current = $this->redis->hget($key, (string) $channelId);

        if ($current !== null && (int) $current >= $pts) {
            return; // already ahead
        }

        $this->redis->hset($key, (string) $channelId, (string) $pts);
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Last observed pts for a channel, or null.
     */
    public function getChannelPts(int $accountId, int $channelId): ?int
    {
        $val = $this->redis->hget(self::channelKey($accountId), (string) $channelId);

        return $val !== null ? (int) $val : null;
    }

    private static function stateKey(int $accountId): string
    {
        return "tg:pts:{$accountId}";
    }

    private static function channelKey(int $accountId): string
    {
        return "tg:pts:{$accountId}:channels";
    }
}
