<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getPasskeys (crc32 ea1f0c52), returns account.Passkeys. */
final class TlAccountGetPasskeysData extends Data
{
    public const METHOD = 'account.getPasskeys';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
