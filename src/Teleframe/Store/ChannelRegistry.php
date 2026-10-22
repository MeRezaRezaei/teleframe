<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use RuntimeException;

/**
 * Channel registry (verbatim S5): managed channels carry a TFREGISTRY1
 * message declaring what they are, so a FRESH LOGIN (new account scope,
 * empty local tables) can walk dialogs, find the registry messages and
 * rebuild local truth — "each time i logged a telegram i understands
 * the previous data chanels the backup chanels".
 *
 * Channel kinds by title prefix:
 *   teleframe-store:<bucket>  — TStore buckets (object + chunk docs)
 *   teleframe-data:<bucket>   — data channels: media posted for later
 *                               use elsewhere; indexed by final hashes
 *   teleframe-backup:<set>    — legacy Backup vaults (frozen, still
 *                               classified so the registry sees them)
 *
 * Registry message wire shape:
 *   TFREGISTRY1: <kind>/<bucket>
 *   ```json
 *   { "version": 1, "kind": "...", "bucket": "...", "created": "..." }
 *   ```
 */
final class ChannelRegistry
{
    public const MARKER = 'TFREGISTRY1:';

    public const FENCE = '```json';

    public const FENCE_END = '```';

    public const VERSION = 1;

    public const KIND_STORE = 'store';

    public const KIND_DATA = 'data';

    public const KIND_BACKUP = 'backup';

    public const PREFIX_STORE = 'teleframe-store:';

    public const PREFIX_DATA = 'teleframe-data:';

    public const PREFIX_BACKUP = 'teleframe-backup:';

    /**
     * Classify a channel title into {kind, bucket} or null when the
     * title is not a managed channel. Zero regex: str_starts_with peel.
     *
     * @return array{kind: string, bucket: string}|null
     */
    public static function classifyTitle(string $title): ?array
    {
        foreach ([
            [self::PREFIX_STORE, self::KIND_STORE],
            [self::PREFIX_DATA, self::KIND_DATA],
            [self::PREFIX_BACKUP, self::KIND_BACKUP],
        ] as [$prefix, $kind]) {
            if (str_starts_with($title, $prefix)) {
                $bucket = substr($title, strlen($prefix));

                return $bucket === '' ? null : ['kind' => $kind, 'bucket' => $bucket];
            }
        }

        return null;
    }

    /**
     * Canonical title for a bucket of a kind.
     */
    public static function titleFor(string $kind, string $bucket): string
    {
        return self::prefixFor($kind).$bucket;
    }

    /**
     * Render the registry message text for a managed channel.
     *
     * @param  array<string, mixed>|null  $extra  optional extra keys merged into the payload
     */
    public static function render(string $kind, string $bucket, ?array $extra = null): string
    {
        $payload = [
            'version' => self::VERSION,
            'kind' => $kind,
            'bucket' => $bucket,
            'created' => gmdate('c'),
        ];

        if (is_array($extra)) {
            foreach ($extra as $key => $value) {
                if (! array_key_exists($key, $payload)) {
                    $payload[$key] = $value;
                }
            }
        }

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return self::MARKER.' '.$kind.'/'.$bucket."\n".
            self::FENCE."\n".$json."\n".self::FENCE_END;
    }

    /**
     * Parse a registry message. Returns {kind, bucket, created} or null
     * when the text is not a registry message. Zero-regex fence scan.
     *
     * @return array{kind: string, bucket: string, created: string}|null
     */
    public static function parse(string $text): ?array
    {
        $firstLine = self::firstLine($text);

        if (! str_starts_with($firstLine, self::MARKER.' ')) {
            return null;
        }

        $header = trim(substr($firstLine, strlen(self::MARKER) + 1));
        $slash = strpos($header, '/');
        if ($slash === false || $slash === 0 || $slash === strlen($header) - 1) {
            return null;
        }

        $kind = substr($header, 0, $slash);
        $bucket = substr($header, $slash + 1);

        if (! in_array($kind, [self::KIND_STORE, self::KIND_DATA, self::KIND_BACKUP], true)) {
            return null;
        }

        $json = self::extractFencedJson($text);
        if ($json === null) {
            throw new RuntimeException("TFREGISTRY1 message for {$kind}/{$bucket} has no JSON fence");
        }

        $decoded = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! is_string($decoded['kind'] ?? null) || ! is_string($decoded['bucket'] ?? null)) {
            throw new RuntimeException("TFREGISTRY1 message for {$kind}/{$bucket} carries a bad payload");
        }

        if ($decoded['kind'] !== $kind || $decoded['bucket'] !== $bucket) {
            throw new RuntimeException('TFREGISTRY1 header does not match its JSON payload');
        }

        $created = $decoded['created'] ?? '';
        if (! is_string($created) || $created === '') {
            $created = '';
        }

        return ['kind' => $kind, 'bucket' => $bucket, 'created' => $created];
    }

    private static function prefixFor(string $kind): string
    {
        return match ($kind) {
            self::KIND_STORE => self::PREFIX_STORE,
            self::KIND_DATA => self::PREFIX_DATA,
            self::KIND_BACKUP => self::PREFIX_BACKUP,
            default => throw new \InvalidArgumentException("unknown kind {$kind}"),
        };
    }

    private static function extractFencedJson(string $text): ?string
    {
        $lines = explode("\n", $text);
        $collecting = false;
        $collected = [];

        foreach ($lines as $line) {
            $trimmed = trim($line, "\r");

            if (! $collecting) {
                if ($trimmed === self::FENCE) {
                    $collecting = true;
                }

                continue;
            }

            if ($trimmed === self::FENCE_END) {
                return implode("\n", $collected);
            }

            $collected[] = $trimmed;
        }

        return $collecting ? implode("\n", $collected) : null;
    }

    private static function firstLine(string $text): string
    {
        $newline = strpos($text, "\n");

        return $newline === false ? $text : substr($text, 0, $newline);
    }
}
