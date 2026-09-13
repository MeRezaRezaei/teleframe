<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSearchResultsPositions (crc32 9c7f2f10), returns messages.SearchResultsPositions. */
final class TlMessagesGetSearchResultsPositionsData extends Data
{
    public const METHOD = 'messages.getSearchResultsPositions';

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
    public int $limit,
    ) {
    }
}
