<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteParticipantReactions (crc32 a0b80cf8), returns Bool. */
final class TlMessagesDeleteParticipantReactionsData extends Data
{
    public const METHOD = 'messages.deleteParticipantReactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $participant,
    ) {
    }
}
