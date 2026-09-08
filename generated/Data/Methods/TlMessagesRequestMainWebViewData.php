<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.requestMainWebView (crc32 c9e01e7b), returns WebViewResult. */
final class TlMessagesRequestMainWebViewData extends Data
{
    public const METHOD = 'messages.requestMainWebView';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $compact,
    public ?bool $fullscreen,
    public mixed $peer,
    public mixed $bot,
    public ?string $startParam,
    public mixed $themeParams,
    public string $platform,
    ) {
    }
}
