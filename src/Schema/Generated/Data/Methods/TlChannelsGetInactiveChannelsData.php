<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getInactiveChannels (crc32 11e831ee), returns messages.InactiveChats. */
final class TlChannelsGetInactiveChannelsData extends Data
{
    public const METHOD = 'channels.getInactiveChannels';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
