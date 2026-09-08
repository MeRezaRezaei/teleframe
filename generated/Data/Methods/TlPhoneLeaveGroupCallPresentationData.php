<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.leaveGroupCallPresentation (crc32 1c50d144), returns Updates. */
final class TlPhoneLeaveGroupCallPresentationData extends Data
{
    public const METHOD = 'phone.leaveGroupCallPresentation';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    ) {
    }
}
