<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.requestAppWebView (crc32 53618bce), returns WebViewResult. */
final class TlMessagesRequestAppWebViewData extends Data
{
    public const METHOD = 'messages.requestAppWebView';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $writeAllowed,
    public ?bool $compact,
    public ?bool $fullscreen,
    public mixed $peer,
    public mixed $app,
    public ?string $startParam,
    public mixed $themeParams,
    public string $platform,
    ) {
    }
}
