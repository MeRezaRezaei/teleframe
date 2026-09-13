<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSuggestedDialogFilters (crc32 a29cd42c), returns Vector<DialogFilterSuggested>. */
final class TlMessagesGetSuggestedDialogFiltersData extends Data
{
    public const METHOD = 'messages.getSuggestedDialogFilters';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
