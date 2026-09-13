<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.getLocated (crc32 d348bc44), returns Updates. */
final class TlContactsGetLocatedData extends Data
{
    public const METHOD = 'contacts.getLocated';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $background,
    public mixed $geoPoint,
    public ?int $selfExpires,
    ) {
    }
}
