<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getContentSettings (crc32 8b9b4dae), returns account.ContentSettings. */
final class TlAccountGetContentSettingsData extends Data
{
    public const METHOD = 'account.getContentSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
