<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updatePasswordSettings (crc32 a59b102f), returns Bool. */
final class TlAccountUpdatePasswordSettingsData extends Data
{
    public const METHOD = 'account.updatePasswordSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $password,
    public mixed $newSettings,
    ) {
    }
}
