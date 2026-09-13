<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getWebPage (crc32 8d9692a3), returns messages.WebPage. */
final class TlMessagesGetWebPageData extends Data
{
    public const METHOD = 'messages.getWebPage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $url,
    public int $hash,
    ) {
    }
}
