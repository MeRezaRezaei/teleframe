<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.saveRecentSticker (crc32 392718f8), returns Bool. */
final class TlMessagesSaveRecentStickerData extends Data
{
    public const METHOD = 'messages.saveRecentSticker';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $attached,
    public mixed $id,
    public mixed $unsave,
    ) {
    }
}
