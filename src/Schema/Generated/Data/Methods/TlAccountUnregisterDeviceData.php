<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.unregisterDevice (crc32 6a0d3206), returns Bool. */
final class TlAccountUnregisterDeviceData extends Data
{
    public const METHOD = 'account.unregisterDevice';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $tokenType,
    public string $token,
    public array $otherUids,
    ) {
    }
}
