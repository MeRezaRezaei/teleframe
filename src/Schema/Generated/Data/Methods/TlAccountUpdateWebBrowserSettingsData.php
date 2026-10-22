<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateWebBrowserSettings (crc32 9adf82fe), returns account.WebBrowserSettings. */
final class TlAccountUpdateWebBrowserSettingsData extends Data
{
    public const METHOD = 'account.updateWebBrowserSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $openExternalBrowser,
    public ?bool $displayCloseButton,
    ) {
    }
}
