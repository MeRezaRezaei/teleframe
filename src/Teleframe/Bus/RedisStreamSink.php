<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;

/**
 * Teleframe sink that mirrors every raw update into the shared Redis
 * stream (StreamSchema::STREAM) so any number of accounts can feed one
 * consumer group. Stream fields are the canonical entry triple:
 * account_id, update (StreamSchema JSON), ts.
 *
 * Sink semantics: always true — an appended entry is consumed as far as
 * this sink is concerned. Returning false ("not now") is reserved for a
 * future backpressure path; per UpdateSinkInterface, soft refusals must
 * not throw.
 */
final class RedisStreamSink implements UpdateSinkInterface
{
    public function __construct(
        private readonly RedisConnectionContract $redis,
        private readonly int $accountId,
    ) {}

    public function handle(array $update, ?string $source = null): bool
    {
        $ts = time();

        $this->redis->xadd(StreamSchema::STREAM, '*', [
            'account_id' => (string) $this->accountId,
            'update' => StreamSchema::encode([
                'account_id' => $this->accountId,
                'update' => $update,
                'ts' => $ts,
            ]),
            'ts' => (string) $ts,
        ]);

        return true;
    }

    /**
     * Derive the tenancy account id from a teleframe source string.
     *
     * Numeric sources are user-account ids; anything else is treated as a
     * bot token ("id:hash") — the id part before the colon wins when it is
     * numeric. Unknown shapes degrade to 0 (unattributed), never throw.
     */
    public static function accountIdFor(?string $source): int
    {
        if ($source === null || $source === '') {
            return 0;
        }

        if (ctype_digit($source)) {
            return (int) $source;
        }

        $id = str_contains($source, ':') ? substr($source, 0, (int) strpos($source, ':')) : '';

        return $id !== '' && ctype_digit($id) ? (int) $id : 0;
    }
}
