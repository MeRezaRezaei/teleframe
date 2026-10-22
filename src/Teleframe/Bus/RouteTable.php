<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

/**
 * The bus route map (plan Task 2): a Redis hash of pattern => target
 * stream name, read hot by every consumer batch.
 *
 * Matching is deliberately prefix-only string work — a pattern may end
 * in a single `*` ("updateNewMessage*" matches every constructor that
 * starts with "updateNewMessage"); no glob, no regex. The FIRST pattern
 * in hash order that matches wins, so ordering of `set()` calls is
 * meaningful. An empty table (or no matching pattern) returns null,
 * which is the consumer's default ingest path.
 */
final class RouteTable
{
    public function __construct(
        private readonly RedisConnectionContract $redis,
    ) {}

    /**
     * All live routes in hash order.
     *
     * @return array<string, string> pattern => target stream name
     */
    public function all(): array
    {
        return $this->redis->hgetall(StreamSchema::ROUTES_KEY);
    }

    /**
     * Add (or retarget) one pattern. Overwrites an existing pattern.
     *
     * This writes the hash and nothing else: there is no publisher on
     * this path, and StreamSchema::RELOAD_CHANNEL carries no
     * route-change signal.
     */
    public function set(string $pattern, string $target): void
    {
        $this->redis->hset(StreamSchema::ROUTES_KEY, $pattern, $target);
    }

    /**
     * Target stream for an update, or null for the default ingest path.
     *
     * A trailing `*` is stripped and the rest treated as a constructor
     * prefix; patterns without `*` match their exact prefix the same
     * way. A bare `*` (empty prefix) matches everything, including
     * updates that carry no `_` constructor at all.
     *
     * This performs a full all() read of the hash. Callers on a hot
     * path should read the snapshot once and use matchIn() instead.
     *
     * @param  array<string, mixed>  $update
     */
    public function match(array $update): ?string
    {
        return self::matchIn($this->all(), $update);
    }

    /**
     * Match against an ALREADY-READ route snapshot, with no Redis call.
     *
     * match() reads the whole hash through all() and then delegates
     * here; the ingest consumer reads the snapshot once per
     * consumeOnce() pass and delegates per entry, so a batch of N
     * entries costs ONE HGETALL of StreamSchema::ROUTES_KEY instead of N.
     * Matching is pure string work — first pattern in hash order wins.
     *
     * @param  array<string, string>  $routes  A snapshot from all().
     * @param  array<string, mixed>  $update
     */
    public static function matchIn(array $routes, array $update): ?string
    {
        return self::matchConstructorIn($routes, (string) ($update['_'] ?? ''));
    }

    /**
     * Match a bare CONSTRUCTOR name against an already-read route snapshot.
     *
     * ## WHY THE NAME, AND NOT THE PAYLOAD, IS THE MATCHING INPUT NOW
     *
     * A v2 bus entry carries no payload (D-039): the row is the system of
     * record and the entry names it. Routing still has to work, because
     * `RouteTable::matchIn()` has always read exactly one thing — the update's
     * constructor — and that string is published in the entry for precisely
     * this reason (D-042). So the entry arrives already holding the only
     * value matching needs, and the entry reader passes it here directly
     * instead of rebuilding a one-key array to carry it.
     *
     * matchIn() keeps its payload-array face for a caller that still HAS a
     * payload, and both land on this one implementation, so the two cannot
     * disagree about what a route matches.
     *
     * @param  array<string, string>  $routes  A snapshot from all().
     */
    public static function matchConstructorIn(array $routes, string $constructor): ?string
    {
        foreach ($routes as $pattern => $target) {
            $prefix = str_ends_with($pattern, '*') ? substr($pattern, 0, -1) : $pattern;

            if (str_starts_with($constructor, $prefix)) {
                return $target;
            }
        }

        return null;
    }
}
