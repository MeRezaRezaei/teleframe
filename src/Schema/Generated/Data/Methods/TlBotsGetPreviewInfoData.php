<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getPreviewInfo (crc32 423ab3ad), returns bots.PreviewInfo. */
final class TlBotsGetPreviewInfoData extends Data
{
    public const METHOD = 'bots.getPreviewInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $langCode,
    ) {
    }
}
