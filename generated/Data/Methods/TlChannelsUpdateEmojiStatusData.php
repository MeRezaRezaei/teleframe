<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.updateEmojiStatus (crc32 f0d3e6a8), returns Updates. */
final class TlChannelsUpdateEmojiStatusData extends Data
{
    public const METHOD = 'channels.updateEmojiStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $emojiStatus,
    ) {
    }
}
