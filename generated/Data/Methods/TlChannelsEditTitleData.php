<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.editTitle (crc32 566decd0), returns Updates. */
final class TlChannelsEditTitleData extends Data
{
    public const METHOD = 'channels.editTitle';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public string $title,
    ) {
    }
}
