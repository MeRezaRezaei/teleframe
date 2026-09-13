<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.updateColor (crc32 d8aa3671), returns Updates. */
final class TlChannelsUpdateColorData extends Data
{
    public const METHOD = 'channels.updateColor';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forProfile,
    public mixed $channel,
    public ?int $color,
    public ?int $backgroundEmojiId,
    ) {
    }
}
