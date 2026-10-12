<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleJoinToSend (crc32 e4cb9580), returns Updates. */
final class TlChannelsToggleJoinToSendData extends Data
{
    public const METHOD = 'channels.toggleJoinToSend';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $enabled,
    ) {
    }
}
