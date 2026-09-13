<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.requestWebView (crc32 269dc2c1), returns WebViewResult. */
final class TlMessagesRequestWebViewData extends Data
{
    public const METHOD = 'messages.requestWebView';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $fromBotMenu,
    public ?bool $silent,
    public ?bool $compact,
    public ?bool $fullscreen,
    public mixed $peer,
    public mixed $bot,
    public ?string $url,
    public ?string $startParam,
    public mixed $themeParams,
    public string $platform,
    public mixed $replyTo,
    public mixed $sendAs,
    ) {
    }
}
