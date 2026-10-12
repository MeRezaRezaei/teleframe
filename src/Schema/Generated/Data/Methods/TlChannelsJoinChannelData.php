<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.joinChannel (crc32 7f6a1e22), returns messages.ChatInviteJoinResult. */
final class TlChannelsJoinChannelData extends Data
{
    public const METHOD = 'channels.joinChannel';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    ) {
    }
}
