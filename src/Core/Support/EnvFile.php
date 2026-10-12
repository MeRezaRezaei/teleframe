<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Support;

use Dotenv\Dotenv;

/**
 * Tiny .env reader/writer for CLI flows. Laravel has no official .env
 * writer (it treats .env as deploy-time config), so this stays ours —
 * deliberately: reader parses KEY=VALUE lines, writer upserts one key.
 */
final class EnvFile
{
    /** @return array<string, string> */
    public static function read(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }
        $parsed = Dotenv::parse((string) file_get_contents($path));

        return array_map(strval(...), $parsed);
    }

    /**
     * Inserts or replaces KEY="value" in the .env at $path.
     *
     * The value is written into a DOUBLE-QUOTED dotenv string, so it must be
     * escaped for that string: a bare `"` ends the value, and a value carrying
     * `"` + newline (anything a CLI operator can type) would otherwise close
     * the line and append forged `KEY=value` lines. Backslash first, then the
     * quote — escaping the quote is what confines the whole value, newlines
     * included, inside the one key.
     */
    public static function upsert(string $path, string $key, string $value): void
    {
        $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
        $prefix = $key.'=';
        $escaped = self::escape($value);
        $found = false;
        foreach ($lines as $i => $line) {
            if (str_starts_with(ltrim($line), $prefix)) {
                $lines[$i] = $key.'="'.$escaped.'"';
                $found = true; // no break: rewrite EVERY duplicate (regex-era replace semantics)
            }
        }
        if (! $found) {
            $lines[] = $key.'="'.$escaped.'"';
        }
        file_put_contents($path, implode("\n", $lines)."\n");
    }

    /** Escape a value for a double-quoted dotenv string. */
    private static function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
