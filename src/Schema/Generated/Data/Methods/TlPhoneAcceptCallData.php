<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.acceptCall (crc32 3bd2b4a0), returns phone.PhoneCall. */
final class TlPhoneAcceptCallData extends Data
{
    public const METHOD = 'phone.acceptCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $gB,
    public mixed $protocol,
    ) {
    }
}
