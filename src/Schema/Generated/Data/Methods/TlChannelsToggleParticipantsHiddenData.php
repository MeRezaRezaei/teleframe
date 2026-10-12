<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleParticipantsHidden (crc32 6a6e7854), returns Updates. */
final class TlChannelsToggleParticipantsHiddenData extends Data
{
    public const METHOD = 'channels.toggleParticipantsHidden';

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
