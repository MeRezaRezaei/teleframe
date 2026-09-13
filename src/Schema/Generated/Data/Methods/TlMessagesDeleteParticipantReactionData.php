<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteParticipantReaction (crc32 e3b7f82c), returns Updates. */
final class TlMessagesDeleteParticipantReactionData extends Data
{
    public const METHOD = 'messages.deleteParticipantReaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public mixed $participant,
    ) {
    }
}
