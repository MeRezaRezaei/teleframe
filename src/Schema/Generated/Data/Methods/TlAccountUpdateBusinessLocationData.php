<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateBusinessLocation (crc32 9e6b131a), returns Bool. */
final class TlAccountUpdateBusinessLocationData extends Data
{
    public const METHOD = 'account.updateBusinessLocation';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $geoPoint,
    public ?string $address,
    ) {
    }
}
