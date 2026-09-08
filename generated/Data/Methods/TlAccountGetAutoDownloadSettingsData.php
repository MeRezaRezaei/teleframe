<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getAutoDownloadSettings (crc32 56da0b3f), returns account.AutoDownloadSettings. */
final class TlAccountGetAutoDownloadSettingsData extends Data
{
    public const METHOD = 'account.getAutoDownloadSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
