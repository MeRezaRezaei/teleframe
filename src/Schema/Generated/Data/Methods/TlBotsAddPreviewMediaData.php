<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.addPreviewMedia (crc32 17aeb75a), returns BotPreviewMedia. */
final class TlBotsAddPreviewMediaData extends Data
{
    public const METHOD = 'bots.addPreviewMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $langCode,
    public mixed $media,
    ) {
    }
}
