<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use RuntimeException;

/**
 * TFSTORE1 metadata message (verbatim S4): every stored object is
 * described by a Telegram message whose first line is the marker +
 * object name, followed by a fenced, pretty-printed JSON block of the
 * ObjectManifest — machine-parseable via the marker AND spottable in
 * Telegram clients (the pretty JSON renders as a readable block).
 *
 * Wire shape:
 *
 *   TFSTORE1: reports/q3.zip
 *   ```json
 *   {
 *       "version": 2,
 *       ...
 *   }
 *   ```
 *
 * Parsing uses zero regex: the marker is peeled with str_starts_with,
 * the fence with line-based scanning.
 *
 * TRUST: this is still a plain, editable chat message — the marker makes a
 * message RECOGNISABLE, it does not make it authentic. Authenticity lives
 * in the manifest body: canonical chunk order (ObjectManifest) plus, for
 * sealed objects, the keyed `mac` field (Store\ManifestMac). Raw objects
 * carry no key and therefore no mac; their guarantee is the whole-object
 * sha256 StoreService compares the assembled bytes against.
 */
final class MetadataMessage
{
    public const MARKER = 'TFSTORE1:';

    public const FENCE = '```json';

    public const FENCE_END = '```';

    /**
     * Render the message text for a manifest.
     */
    public static function render(ObjectManifest $manifest): string
    {
        $json = json_encode(
            $manifest->toArray(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
        );

        return self::MARKER.' '.$manifest->name()."\n".
            self::FENCE."\n".$json."\n".self::FENCE_END;
    }

    /**
     * Extract the object name from a message's first line, or null when
     * the message is not a TFSTORE1 metadata message.
     */
    public static function nameFromText(string $text): ?string
    {
        $firstLine = self::firstLine($text);

        if (! str_starts_with($firstLine, self::MARKER.' ')) {
            return null;
        }

        $name = trim(substr($firstLine, strlen(self::MARKER) + 1));

        return $name === '' ? null : $name;
    }

    /**
     * Parse a metadata message into a manifest, or null when the text is
     * not a TFSTORE1 message. Throws when it IS one but the JSON block
     * is missing/corrupt (that is data loss, not a foreign message).
     */
    public static function parse(string $text): ?ObjectManifest
    {
        $name = self::nameFromText($text);
        if ($name === null) {
            return null;
        }

        $json = self::extractFencedJson($text);
        if ($json === null) {
            throw new RuntimeException("TFSTORE1 message for {$name} has no JSON fence");
        }

        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException("TFSTORE1 message for {$name} carries corrupt JSON: {$e->getMessage()}", 0, $e);
        }
        if (! is_array($decoded)) {
            throw new RuntimeException("TFSTORE1 message for {$name} carries a non-object JSON payload");
        }

        $manifest = ObjectManifest::fromArray($decoded);

        if ($manifest->name() !== $name) {
            throw new RuntimeException(
                "TFSTORE1 message name {$name} does not match manifest name {$manifest->name()}",
            );
        }

        return $manifest;
    }

    /**
     * Zero-regex fence extraction: find the ```json fence line, collect
     * lines until the closing ``` line.
     */
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
