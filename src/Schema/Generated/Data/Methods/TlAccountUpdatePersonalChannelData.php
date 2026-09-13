<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updatePersonalChannel (crc32 d94305e0), returns Bool. */
final class TlAccountUpdatePersonalChannelData extends Data
{
    public const METHOD = 'account.updatePersonalChannel';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    ) {
    }
}
