<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSearchResultsCalendar (crc32 6aa3f6bd), returns messages.SearchResultsCalendar. */
final class TlMessagesGetSearchResultsCalendarData extends Data
{
    public const METHOD = 'messages.getSearchResultsCalendar';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public mixed $savedPeerId,
    public mixed $filter,
    public int $offsetId,
    public int $offsetDate,
    ) {
    }
}
