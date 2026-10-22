<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * PremiumProbe (notification-core capability cluster): the best-effort live
 * probe for a Telegram account's premium flag (design §6.4 / spec §6 Q4 —
 * "per-send flag first, a live users.getFullUser probe is allowed later").
 *
 * The probe is strictly best-effort by ruling: it resolves users.getFullUser
 * for the target user id through the injected callable (the ResilientCall /
 * BotClient::call shape fn(string $method, array $params): array) and returns
 * PremiumStatus::fromBool() on success; on ANY failure — transport exception,
 * malformed response, or a response whose shape carries no premium flag — it
 * returns PremiumStatus::unknown() SILENTLY and never throws, so the notify
 * path is never broken by a probe. Failures are only ever logged at debug
 * level through the optional PSR-3 logger.
 *
 * Results are memoized in a synchronous in-memory cache (plain array keyed by
 * user id, no file/redis) for ttlSeconds (default 60; null = no expiry, 0 =
 * cache disabled). Both positives and unknowns are cached: re-probing a
 * failing target on every send would be its own flood risk.
 *
 * Schema note (this catalog, Layer 229): users.getFullUser returns
 * users.userFull = { full_user: UserFull, chats, users: Vector<User> } and the
 * current userFull constructor carries NO user field — the premium flag rides
 * the User object (user.premium, TL flag bits 28) inside the response's users
 * vector. The default extractor reads that vector first, then falls back to
 * the real-world full_user.user / top-level user shapes, and hosts whose
 * transport differs may inject their own extractor
 * (fn(array $response, int $userId): ?bool — null = flag not determinable).
 */
final class PremiumProbe
{
    /** @var array<int, array{at: int, status: PremiumStatus}> user id => cache row */
    private array $cache = [];

    /** @var callable(string, array<string, mixed>): array<string, mixed> */
    private $call;

    /** @var callable(array<string, mixed>, int): ?bool */
    private $extract;

    private readonly LoggerInterface $logger;

    /**
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $call
     *                                                                              dials one RPC method; the caller supplies the transport
     *                                                                              (ResilientCall-wrapped connection, BotClient::call, a test stub).
     * @param  int|null  $ttlSeconds  sync cache TTL per user id; null = cache
     *                                forever, 0 = always re-probe. Default 60.
     * @param  callable(array<string, mixed>, int): ?bool|null  $extract
     *                                                                    extracts the premium flag from a users.getFullUser response
     *                                                                    (bool = success, null = flag missing/undeterminable -> unknown).
     *                                                                    Defaults to a defensive reader of the schema-accurate shape.
     */
    public function __construct(
        callable $call,
        private readonly ?int $ttlSeconds = 60,
        ?LoggerInterface $logger = null,
        ?callable $extract = null,
    ) {
        $this->call = $call;
        $this->logger = $logger ?? new NullLogger;
        $this->extract = $extract ?? self::defaultExtractor();
    }

    /**
     * Best-effort premium probe for one user id. Never throws: success ->
     * fromBool(), any failure or undeterminable flag -> unknown() (logged at
     * debug when a logger is configured).
     */
    public function probe(int $userId): PremiumStatus
    {
        $now = time();
        $hit = $this->cache[$userId] ?? null;
        if ($hit !== null && ($this->ttlSeconds === null || $now - $hit['at'] < $this->ttlSeconds)) {
            return $hit['status'];
        }

        try {
            $response = ($this->call)('users.getFullUser', ['id' => $userId]);
            $flag = ($this->extract)($response, $userId);
            $status = $flag === null ? PremiumStatus::unknown() : PremiumStatus::fromBool($flag);
        } catch (Throwable $e) {
            $this->logger->debug(
                'PremiumProbe: users.getFullUser failed, treating premium as unknown',
                ['user_id' => $userId, 'method' => 'users.getFullUser', 'error' => $e->getMessage(), 'class' => $e::class],
            );
            $status = PremiumStatus::unknown();
        }

        $this->cache[$userId] = ['at' => $now, 'status' => $status];

        return $status;
    }

    /**
     * Defensive default extractor: primary source is the users vector of the
     * users.userFull response (schema-accurate — Layer 229 userFull carries no
     * user field); fallbacks cover the real-world full_user.user and top-level
     * user shapes hosts may see on other layers/transports. The flag is read
     * under both the `premium` and `is_premium` keys; a target user present but
     * without the flag, or no matching user at all, yields null -> unknown.
     *
     * @return callable(array<string, mixed>, int): ?bool
     */
    private static function defaultExtractor(): callable
    {
        return static function (array $response, int $userId): ?bool {
            $groups = [];

            if (isset($response['users']) && is_array($response['users'])) {
                $groups[] = $response['users'];
            }
            if (isset($response['full_user']) && is_array($response['full_user'])) {
                $fullUser = $response['full_user'];
                if (isset($fullUser['user']) && is_array($fullUser['user'])) {
                    $groups[] = [$fullUser['user']];
                }
            }
            if (isset($response['user']) && is_array($response['user'])) {
                $groups[] = [$response['user']];
            }

            foreach ($groups as $users) {
                foreach ($users as $user) {
                    if (! is_array($user)) {
                        continue;
                    }
                    if (isset($user['id']) && (int) $user['id'] !== $userId) {
                        continue;
                    }
                    if (array_key_exists('premium', $user)) {
                        return (bool) $user['premium'];
                    }
                    if (array_key_exists('is_premium', $user)) {
                        return (bool) $user['is_premium'];
                    }

                    return null; // target found but no flag carried -> unknown
                }
            }

            return null; // no user object with the flag in the response -> unknown
        };
    }
}
