<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.receivedMessages (crc32 05a954c0), returns Vector<ReceivedNotifyMessage>. */
final class TlMessagesReceivedMessagesData extends Data
{
    public const METHOD = 'messages.receivedMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $maxId,
    ) {
    }
}
