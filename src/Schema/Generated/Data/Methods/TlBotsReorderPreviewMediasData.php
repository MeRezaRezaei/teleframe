<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.reorderPreviewMedias (crc32 b627f3aa), returns Bool. */
final class TlBotsReorderPreviewMediasData extends Data
{
    public const METHOD = 'bots.reorderPreviewMedias';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $langCode,
    public array $order,
    ) {
    }
}
