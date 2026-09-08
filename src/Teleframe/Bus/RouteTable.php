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
     * Add (or retarget) one pattern. Overwrites an existing pattern —
     * publishers announce the change on StreamSchema::RELOAD_CHANNEL.
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
     * @param array<string, mixed> $update
     */
    public function match(array $update): ?string
    {
        $constructor = (string) ($update['_'] ?? '');

        foreach ($this->all() as $pattern => $target) {
            $prefix = str_ends_with($pattern, '*') ? substr($pattern, 0, -1) : $pattern;

            if (str_starts_with($constructor, $prefix)) {
                return $target;
            }
        }

        return null;
    }
}
