<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.editLocation (crc32 58e63f6d), returns Bool. */
final class TlChannelsEditLocationData extends Data
{
    public const METHOD = 'channels.editLocation';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $geoPoint,
    public string $address,
    ) {
    }
}
