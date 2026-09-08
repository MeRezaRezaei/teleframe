<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getGameHighScores (crc32 e822649d), returns messages.HighScores. */
final class TlMessagesGetGameHighScoresData extends Data
{
    public const METHOD = 'messages.getGameHighScores';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $id,
    public mixed $userId,
    ) {
    }
}
