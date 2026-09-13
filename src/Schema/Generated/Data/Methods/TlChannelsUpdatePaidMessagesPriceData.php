<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.updatePaidMessagesPrice (crc32 4b12327b), returns Updates. */
final class TlChannelsUpdatePaidMessagesPriceData extends Data
{
    public const METHOD = 'channels.updatePaidMessagesPrice';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $broadcastMessagesAllowed,
    public mixed $channel,
    public int $sendPaidMessagesStars,
    ) {
    }
}
