<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getMessageReadParticipants (crc32 31c1c44f), returns Vector<ReadParticipantDate>. */
final class TlMessagesGetMessageReadParticipantsData extends Data
{
    public const METHOD = 'messages.getMessageReadParticipants';

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
