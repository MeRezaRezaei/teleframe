<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendWebViewData (crc32 dc0242c8), returns Updates. */
final class TlMessagesSendWebViewDataData extends Data
{
    public const METHOD = 'messages.sendWebViewData';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public int $randomId,
    public string $buttonText,
    public string $data,
    ) {
    }
}
