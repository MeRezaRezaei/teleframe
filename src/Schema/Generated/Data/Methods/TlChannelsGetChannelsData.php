<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getChannels (crc32 0a7f6bbb), returns messages.Chats. */
final class TlChannelsGetChannelsData extends Data
{
    public const METHOD = 'channels.getChannels';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
