<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.restrictSponsoredMessages (crc32 9ae91519), returns Updates. */
final class TlChannelsRestrictSponsoredMessagesData extends Data
{
    public const METHOD = 'channels.restrictSponsoredMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $restricted,
    ) {
    }
}
