<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getPasswordSettings (crc32 9cd4eaf9), returns account.PasswordSettings. */
final class TlAccountGetPasswordSettingsData extends Data
{
    public const METHOD = 'account.getPasswordSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $password,
    ) {
    }
}
