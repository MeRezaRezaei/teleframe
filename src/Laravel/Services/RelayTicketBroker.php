<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * One-time relay tickets for the control layer ("sit in the middle").
 *
 * A vault account's session string is a credential and must never leave the
 * server. The web client does not need it: it needs a short-lived proof that
 * it may drive THAT account through our relay. This broker mints an opaque,
 * single-use ticket bound to an account id, and redeems it exactly once
 * before it expires.
 *
 * Storage is the host's cache (same transport as the dashboard login flow).
 * The ticket value itself is a 256-bit random string — nothing is
 * derivable from it, and the mapping ticket → account dies with the TTL.
 *
 * Redeem atomicity (W-11): the get-then-delete read model has a TOCTOU
 * window a racing redeemer can slip through, minting a second authenticated
 * socket from the same ticket. When the underlying store is Redis the
 * consume is a single atomic GETDEL/Lua script (the common production
 * store), so redemption is exactly-once even under concurrency; on non-Redis
 * stores the contract degrades to best-effort and the docblock stays honest
 * about it.
 */
final class RelayTicketBroker
{
    private const CACHE_PREFIX = 'telegram_relay_ticket:';

    private const REDEEM_SCRIPT = <<<'LUA'
local value = redis.call('GET', KEYS[1])
if value then
  redis.call('DEL', KEYS[1])
end
return value
LUA;

    private readonly Repository $cache;

    public function __construct(?Repository $cache = null)
    {
        $this->cache = $cache ?? Cache::store();
    }

    /**
     * Mint a short-lived single-use ticket for the given account.
     *
     * When a Laravel user id is supplied it is bound into the ticket payload
     * so the redeemer learns it too — the host's relay policy middlewares
     * key on the per-connection identity without ever seeing the session
     * string. Legacy callers that only care about the account may omit the
     * user id entirely; nothing about the ticket shape changes.
     *
     * The READ-ONLY INTENT is bound the same way (`read_only`), because the
     * relay terminal enforces the read-only allow-list from the ticket a
     * connection redeemed — a per-request HTTP scope frame never reaches the
     * console daemon, so the ticket is the only source that exists in the
     * process which actually enforces (finding C5). Omitting the flag stores
     * an UNDETERMINED intent, which the daemon reads as read-only: deny by
     * default.
     *
     * @param  bool|null  $readOnly  the minting request's read-only intent; null
     *                               stores an undetermined (⇒ read-only) grant
     * @return array{ticket: string, account_id: int, user_id: int|string|null, read_only: bool|null, expires_at: string}
     */
    public function mint(int $accountId, int|string|null $userId = null, ?bool $readOnly = null): array
    {
        $ticket = (string) Str::uuid().'.'.bin2hex(random_bytes(32));
        $ttlSeconds = max(60, (int) config('teleframe.telegram_web.ticket_ttl_minutes', 10) * 60);
        $expiresAt = now()->addSeconds($ttlSeconds);

        $this->cache->put(
            self::CACHE_PREFIX.$ticket,
            json_encode([
                'account_id' => $accountId,
                'user_id' => $userId,
                'read_only' => $readOnly,
                'consumed' => false,
            ], JSON_THROW_ON_ERROR),
            $expiresAt,
        );

        return [
            'ticket' => $ticket,
            'account_id' => $accountId,
            'user_id' => $userId,
            'read_only' => $readOnly,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Redeem a ticket exactly once. Returns the bound account id, or null
     * when the ticket is unknown, expired, or already consumed.
     *
     * Kept as-is for backward compatibility; {@see self::redeemIdentity()}
     * returns the account id together with the bound user id.
     *
     * On a Redis-backed cache the consume is a single atomic GET+DEL, so a
     * concurrent redeem of the same ticket necessarily loses the race and
     * observes null (W-11 — no TOCTOU window). On any other store the
     * read-then-delete fallback is best-effort: it still guarantees the
     * ticket die with its first consumption, but two racing redeemers on a
     * non-atomic store could both observe the payload.
     */
    public function redeem(string $ticket): ?int
    {
        $payload = $this->consumePayload($ticket);

        return $payload['account_id'] ?? null;
    }

    /**
     * Redeem a ticket exactly once and return the full bound identity — the
     * account id plus the Laravel user id captured at mint time (null when
     * the minting request had no authenticated user).
     *
     * @return array{account_id: int, user_id: int|string|null}|null null when
     *                                                               the ticket is unknown, expired, or already consumed.
     */
    public function redeemIdentity(string $ticket): ?array
    {
        $payload = $this->consumePayload($ticket);
        if ($payload === null) {
            return null;
        }

        return [
            'account_id' => $payload['account_id'],
            'user_id' => $payload['user_id'] ?? null,
        ];
    }

    /**
     * Redeem a ticket exactly once and return the FULL grant: the identity
     * plus the read-only intent the minting request carried. This is the
     * shape the relay daemon's ticket validator records into
     * RelayReadOnlyGrants so the vault terminal can arm its read-only guard
     * from the ticket the socket redeemed (finding C5).
     *
     * `read_only` is tri-state on purpose: true = a read-only grant, false =
     * an explicit read-write grant, null = the ticket carries no intent
     * (a ticket minted before the flag existed, or by a caller that declined
     * to state one). The registry reads null as READ-ONLY — deny by default.
     *
     * @return array{account_id: int, user_id: int|string|null, read_only: bool|null}|null
     *                                                                                     null when the ticket is unknown,
     *                                                                                     expired, or already consumed.
     */
    public function redeemGrant(string $ticket): ?array
    {
        $payload = $this->consumePayload($ticket);
        if ($payload === null) {
            return null;
        }

        $readOnly = $payload['read_only'] ?? null;
        if (! is_bool($readOnly)) {
            $readOnly = null;
        }

        return [
            'account_id' => $payload['account_id'],
            'user_id' => $payload['user_id'] ?? null,
            'read_only' => $readOnly,
        ];
    }

    /**
     * Atomically read-and-destroy the ticket's stored JSON. Returns the
     * normalized payload, or null when the ticket is vacant.
     *
     * On a Redis-backed cache the consume is a single atomic GET+DEL, so a
     * concurrent redeem of the same ticket necessarily loses the race and
     * observes null (W-11 — no TOCTOU window). On any other store the
     * read-then-delete fallback is best-effort: it still guarantees the
     * ticket die with its first consumption, but two racing redeemers on a
     * non-atomic store could both observe the payload.
     *
     * @return array{account_id: int, user_id: int|string|null, read_only: bool|null}|null
     */
    private function consumePayload(string $ticket): ?array
    {
        if ($ticket === '') {
            return null;
        }

        $key = self::CACHE_PREFIX.$ticket;
        $raw = null;

        try {
            $store = $this->cache instanceof \Illuminate\Cache\Repository
                ? $this->cache->getStore()
                : null;
            if ($store instanceof RedisStore) {
                $connection = $store->connection();
                if ($connection instanceof PhpRedisConnection) {
                    $raw = $connection->eval(self::REDEEM_SCRIPT, 1, $key);
                } elseif ($connection instanceof PredisConnection) {
                    $raw = $connection->eval(self::REDEEM_SCRIPT, 1, $key);
                }
            }
        } catch (Throwable) {
            $raw = null;
        }

        if ($raw === false || $raw === null) {
            // Non-Redis store (or a redis connection that refused the eval):
            // best-effort read-then-delete fallback, the historical contract.
            $raw = $this->cache->get($key);
            if (is_string($raw) && $raw !== '') {
                $this->cache->forget($key);
            }
        }

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return null;
        }

        $accountId = isset($payload['account_id']) ? (int) $payload['account_id'] : null;
        if ($accountId === null || $accountId < 1) {
            return null;
        }

        $userId = $payload['user_id'] ?? null;
        if (! is_int($userId) && ! is_string($userId)) {
            $userId = null;
        }
        if (is_string($userId) && $userId === '') {
            $userId = null;
        }

        $readOnly = $payload['read_only'] ?? null;
        if (! is_bool($readOnly)) {
            $readOnly = null;
        }

        return ['account_id' => $accountId, 'user_id' => $userId, 'read_only' => $readOnly];
    }
}
