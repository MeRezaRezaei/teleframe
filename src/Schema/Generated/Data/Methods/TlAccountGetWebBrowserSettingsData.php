<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getWebBrowserSettings (crc32 56655768), returns account.WebBrowserSettings. */
final class TlAccountGetWebBrowserSettingsData extends Data
{
    public const METHOD = 'account.getWebBrowserSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
