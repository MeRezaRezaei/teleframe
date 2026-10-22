<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getChannelRecommendations (crc32 25a71742), returns messages.Chats. */
final class TlChannelsGetChannelRecommendationsData extends Data
{
    public const METHOD = 'channels.getChannelRecommendations';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $channel,
    ) {
    }
}
