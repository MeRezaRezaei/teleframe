<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateDeviceLocked (crc32 38df3532), returns Bool. */
final class TlAccountUpdateDeviceLockedData extends Data
{
    public const METHOD = 'account.updateDeviceLocked';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $period,
    ) {
    }
}
