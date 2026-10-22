<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getAccountTTL (crc32 08fc711d), returns AccountDaysTTL. */
final class TlAccountGetAccountTTLData extends Data
{
    public const METHOD = 'account.getAccountTTL';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
