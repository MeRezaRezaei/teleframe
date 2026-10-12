<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.searchSentMedia (crc32 107e31a0), returns messages.Messages. */
final class TlMessagesSearchSentMediaData extends Data
{
    public const METHOD = 'messages.searchSentMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $q,
    public mixed $filter,
    public int $limit,
    ) {
    }
}
