<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.deletePreviewMedia (crc32 2d0135b3), returns Bool. */
final class TlBotsDeletePreviewMediaData extends Data
{
    public const METHOD = 'bots.deletePreviewMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $langCode,
    public array $media,
    ) {
    }
}
