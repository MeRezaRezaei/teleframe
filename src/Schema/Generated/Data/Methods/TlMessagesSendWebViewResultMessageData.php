<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendWebViewResultMessage (crc32 0a4314f5), returns WebViewMessageSent. */
final class TlMessagesSendWebViewResultMessageData extends Data
{
    public const METHOD = 'messages.sendWebViewResultMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $botQueryId,
    public mixed $result,
    ) {
    }
}
