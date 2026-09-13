<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.toggleWebBrowserSettingsException (crc32 60ed4229), returns Updates. */
final class TlAccountToggleWebBrowserSettingsExceptionData extends Data
{
    public const METHOD = 'account.toggleWebBrowserSettingsException';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $delete,
    public mixed $openExternalBrowser,
    public string $url,
    ) {
    }
}
