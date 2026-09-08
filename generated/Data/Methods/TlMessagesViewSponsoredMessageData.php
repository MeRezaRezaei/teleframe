<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.viewSponsoredMessage (crc32 269e3643), returns Bool. */
final class TlMessagesViewSponsoredMessageData extends Data
{
    public const METHOD = 'messages.viewSponsoredMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $randomId,
    ) {
    }
}
