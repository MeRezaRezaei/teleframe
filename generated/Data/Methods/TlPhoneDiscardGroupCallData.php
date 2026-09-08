<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.discardGroupCall (crc32 7a777135), returns Updates. */
final class TlPhoneDiscardGroupCallData extends Data
{
    public const METHOD = 'phone.discardGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    ) {
    }
}
