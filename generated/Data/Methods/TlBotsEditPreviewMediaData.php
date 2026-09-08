<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.editPreviewMedia (crc32 8525606f), returns BotPreviewMedia. */
final class TlBotsEditPreviewMediaData extends Data
{
    public const METHOD = 'bots.editPreviewMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $langCode,
    public mixed $media,
    public mixed $newMedia,
    ) {
    }
}
