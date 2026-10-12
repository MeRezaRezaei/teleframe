<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.setGlobalPrivacySettings (crc32 1edaaac2), returns GlobalPrivacySettings. */
final class TlAccountSetGlobalPrivacySettingsData extends Data
{
    public const METHOD = 'account.setGlobalPrivacySettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $settings,
    ) {
    }
}
