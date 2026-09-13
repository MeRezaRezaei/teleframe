<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getPreviewMedias (crc32 a2a5594d), returns Vector<BotPreviewMedia>. */
final class TlBotsGetPreviewMediasData extends Data
{
    public const METHOD = 'bots.getPreviewMedias';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    ) {
    }
}
