<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.requestSimpleWebView (crc32 413a3e73), returns WebViewResult. */
final class TlMessagesRequestSimpleWebViewData extends Data
{
    public const METHOD = 'messages.requestSimpleWebView';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $fromSwitchWebview,
    public ?bool $fromSideMenu,
    public ?bool $compact,
    public ?bool $fullscreen,
    public mixed $bot,
    public ?string $url,
    public ?string $startParam,
    public mixed $themeParams,
    public string $platform,
    ) {
    }
}
