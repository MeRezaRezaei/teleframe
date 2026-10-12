<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.receivedQueue (crc32 55a5bb66), returns Vector<long>. */
final class TlMessagesReceivedQueueData extends Data
{
    public const METHOD = 'messages.receivedQueue';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $maxQts,
    ) {
    }
}
