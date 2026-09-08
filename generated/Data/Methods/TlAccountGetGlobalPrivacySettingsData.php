<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getGlobalPrivacySettings (crc32 eb2b4cf6), returns GlobalPrivacySettings. */
final class TlAccountGetGlobalPrivacySettingsData extends Data
{
    public const METHOD = 'account.getGlobalPrivacySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
