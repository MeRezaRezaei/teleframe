<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.startScheduledGroupCall (crc32 5680e342), returns Updates. */
final class TlPhoneStartScheduledGroupCallData extends Data
{
    public const METHOD = 'phone.startScheduledGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    ) {
    }
}
