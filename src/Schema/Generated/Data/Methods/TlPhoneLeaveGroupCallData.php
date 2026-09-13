<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.leaveGroupCall (crc32 500377f9), returns Updates. */
final class TlPhoneLeaveGroupCallData extends Data
{
    public const METHOD = 'phone.leaveGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public int $source,
    ) {
    }
}
