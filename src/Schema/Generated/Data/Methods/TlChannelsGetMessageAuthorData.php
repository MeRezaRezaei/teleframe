<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getMessageAuthor (crc32 ece2a0e6), returns User. */
final class TlChannelsGetMessageAuthorData extends Data
{
    public const METHOD = 'channels.getMessageAuthor';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public int $id,
    ) {
    }
}
