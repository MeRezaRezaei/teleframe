<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.resetTopPeerRating (crc32 1ae373ac), returns Bool. */
final class TlContactsResetTopPeerRatingData extends Data
{
    public const METHOD = 'contacts.resetTopPeerRating';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $category,
    public mixed $peer,
    ) {
    }
}
