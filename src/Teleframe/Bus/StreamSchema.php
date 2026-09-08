<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use InvalidArgumentException;
use JsonException;

/**
 * Canonical wire names and entry codec for the Redis bus (plan Phase 3).
 *
 * Every stream entry (updates + dead-letter + forwarded targets) carries
 * the same stable shape — {account_id, update, ts} — serialized in a fixed
 * key order so payloads are byte-identical for identical entries and can
 * be diffed/verified without drift.
 */
final class StreamSchema
{
    public const STREAM = 'tg:stream:updates';

    public const GROUP = 'teleclient';

    public const CONSUMER = 'ingest-1';

    public const DL = 'tg:stream:dead-letter';

    public const RELOAD_CHANNEL = 'tg:bus:reload';

    public const ROUTES_KEY = 'tg:bus:routes';

    /**
     * Serialize an entry to its canonical JSON form.
     *
     * @param array{account_id: int, update: array<string, mixed>, ts: int} $entry
     * @throws JsonException When the update payload is not JSON-encodable.
     */
    public static function encode(array $entry): string
    {
        $canonical = [
            'account_id' => (int) $entry['account_id'],
            'update' => (array) $entry['update'],
            'ts' => (int) $entry['ts'],
        ];

        return json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Reverse of encode(); normalizes scalar types back to the canonical shape.
     *
     * @param string $payload
     * @return array{account_id: int, update: array<string, mixed>, ts: int}
     * @throws JsonException When the payload is not valid JSON.
     * @throws InvalidArgumentException When the payload is valid JSON but not a canonical entry.
     */
    public static function decode(string $payload): array
    {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Bus entry must be a JSON object, got: ' . $payload);
        }

        foreach (['account_id', 'update', 'ts'] as $key) {
            if (!array_key_exists($key, $decoded)) {
                throw new InvalidArgumentException('Bus entry is missing the "' . $key . '" key.');
            }
        }

        if (!is_array($decoded['update'])) {
            throw new InvalidArgumentException('Bus entry "update" must be an object.');
        }

        return [
            'account_id' => (int) $decoded['account_id'],
            'update' => $decoded['update'],
            'ts' => (int) $decoded['ts'],
        ];
    }
}
