<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.editPhoto (crc32 f12e57c9), returns Updates. */
final class TlChannelsEditPhotoData extends Data
{
    public const METHOD = 'channels.editPhoto';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $photo,
    ) {
    }
}
