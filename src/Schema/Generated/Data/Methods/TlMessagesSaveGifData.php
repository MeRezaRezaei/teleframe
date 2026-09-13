<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.saveGif (crc32 327a30cb), returns Bool. */
final class TlMessagesSaveGifData extends Data
{
    public const METHOD = 'messages.saveGif';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public mixed $unsave,
    ) {
    }
}
