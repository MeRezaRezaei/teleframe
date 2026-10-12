<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDialogFilters (crc32 efd48c89), returns messages.DialogFilters. */
final class TlMessagesGetDialogFiltersData extends Data
{
    public const METHOD = 'messages.getDialogFilters';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
