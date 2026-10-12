<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getBlocked (crc32 9a868f80), returns contacts.Blocked. */
final class TlContactsGetBlockedData extends Data
{
    public const METHOD = 'contacts.getBlocked';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $myStoriesFrom,
    public int $offset,
    public int $limit,
    ) {
    }
}
