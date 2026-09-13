<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAllDrafts (crc32 6a3f8d65), returns Updates. */
final class TlMessagesGetAllDraftsData extends Data
{
    public const METHOD = 'messages.getAllDrafts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
