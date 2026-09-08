<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getMessageEditData (crc32 fda68d36), returns messages.MessageEditData. */
final class TlMessagesGetMessageEditDataData extends Data
{
    public const METHOD = 'messages.getMessageEditData';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $id,
    ) {
    }
}
