<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getOutboxReadDate (crc32 8c4bfe5d), returns OutboxReadDate. */
final class TlMessagesGetOutboxReadDateData extends Data
{
    public const METHOD = 'messages.getOutboxReadDate';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    ) {
    }
}
