<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.migrateChat (crc32 a2875319), returns Updates. */
final class TlMessagesMigrateChatData extends Data
{
    public const METHOD = 'messages.migrateChat';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $chatId,
    ) {
    }
}
