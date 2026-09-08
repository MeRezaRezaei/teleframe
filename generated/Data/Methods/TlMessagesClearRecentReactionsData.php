<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.clearRecentReactions (crc32 9dfeefb4), returns Bool. */
final class TlMessagesClearRecentReactionsData extends Data
{
    public const METHOD = 'messages.clearRecentReactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
