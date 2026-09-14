<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;

/**
 * Update routing cache — the verbatim's "Redis two" (hot-reload settings).
 *
 * Owner verbatim 2026-09-14: "we have registed two kind of redis in our
 * laravel one for default work of redis laravel have and the other for
 * hot relaod the setitngs... the settings are stored in our database tooo
 * and on change there is also the observer that listens to them... the
 * redis two also for chainging the things that the ingester should send
 * as event too so we can controll the things we are going to listen on or
 * ignore in real time".
 *
 * DB (tg_update_routing) stays the source of truth; this cache mirrors it
 * into a Redis hash so the hot ingest path answers routing questions
 * without a DB hit per update. refresh() re-syncs from DB and publishes
 * on the reload channel so listening daemons re-read immediately —
 * real-time listen/ignore control, no restart.
 */
final class UpdateRoutingCache
{
    public const KEY = 'tg:routing:rules';

    public const FIELD_SEPARATOR = ':';

    public function __construct(
        private readonly RedisConnectionContract $redis,
        private readonly ConnectionInterface $db,
    ) {}

    /**
     * Re-sync the cached routing table for one account from the DB.
     */
    public function refresh(int $accountId): int
    {
        $key = self::KEY.self::FIELD_SEPARATOR.$accountId;

        $this->redis->del($key);

        try {
            $rules = $this->db->table('tg_update_routing')
                ->where('account_id', $accountId)
                ->orderByDesc('priority')
                ->get();
        } catch (\Exception) {
            // Settings table not yet migrated — empty rules, safe default.
            $rules = [];
        }

        foreach ($rules as $rule) {
            $this->redis->hset(
                $key,
                (string) $rule->peer_type.self::FIELD_SEPARATOR.(string) $rule->peer_id,
                (string) $rule->mode,
            );
        }

        $this->redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
            'kind' => 'routing',
            'account_id' => $accountId,
        ], JSON_THROW_ON_ERROR));

        return count($rules);
    }

    /**
     * Routing mode for a (account, peer) pair, from the Redis cache.
     *
     * @return string|null UpdateRoutingRule::MODE_* or null when no rule cached
     */
    public function mode(int $accountId, int $peerType, int $peerId): ?string
    {
        $key = self::KEY.self::FIELD_SEPARATOR.$accountId;

        return $this->redis->hget($key, (string) $peerType.self::FIELD_SEPARATOR.(string) $peerId);
    }
}
