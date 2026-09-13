<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.deactivateAllUsernames (crc32 0a245dd3), returns Bool. */
final class TlChannelsDeactivateAllUsernamesData extends Data
{
    public const METHOD = 'channels.deactivateAllUsernames';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    ) {
    }
}
