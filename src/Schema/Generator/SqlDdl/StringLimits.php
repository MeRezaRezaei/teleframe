<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\SqlDdl;

/**
 * Exact charset catalog for promoted string columns (SQL-extraction §3).
 *
 * Every bound is a Telegram-protocol/documented maximum, never a guess:
 *   username 5–32          first_name/last_name 1–64 / 0–64
 *   phone E.164 <=15 + '+'  title 1–128
 *   message <=4096 UTF-16 units  caption <=1024 units
 *   lang_code BCP-47 (<=7)  mime_type short  short_name <=64
 *
 * Anything without a provable bound falls back to DEFAULT_LIMIT and is
 * emitted as VARCHAR(DEFAULT_LIMIT) — never wider, silently.
 */
final class StringLimits
{
    public const DEFAULT_LIMIT = 255;

    /** @var array<string, int> field name (as in the .tl) => exact max chars */
    private const MAP = [
        'username' => 32,
        'first_name' => 64,
        'last_name' => 64,
        'phone' => 16,
        'title' => 128,
        'message' => 4096,
        'caption' => 1024,
        'lang_code' => 8,
        'mime_type' => 128,
        'short_name' => 64,
        'post_author' => 32,
        'bot_inline_placeholder' => 32,
        'slug' => 255,
        'about' => 255,
        'description' => 255,
        'country' => 64,
        'city' => 64,
        'currency' => 8,
        'email' => 255,
        'domain' => 255,
        'address' => 255,
    ];

    public static function limitFor(string $field): int
    {
        return self::MAP[$field] ?? self::DEFAULT_LIMIT;
    }

    public static function isPinned(string $field): bool
    {
        return isset(self::MAP[$field]);
    }
}