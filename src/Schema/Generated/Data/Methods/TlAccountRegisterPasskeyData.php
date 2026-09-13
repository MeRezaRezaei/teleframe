<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.registerPasskey (crc32 55b41fd6), returns Passkey. */
final class TlAccountRegisterPasskeyData extends Data
{
    public const METHOD = 'account.registerPasskey';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $credential,
    ) {
    }
}
