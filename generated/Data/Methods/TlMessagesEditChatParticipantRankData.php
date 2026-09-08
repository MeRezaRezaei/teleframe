<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editChatParticipantRank (crc32 a00f32b0), returns Updates. */
final class TlMessagesEditChatParticipantRankData extends Data
{
    public const METHOD = 'messages.editChatParticipantRank';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $participant,
    public string $rank,
    ) {
    }
}
