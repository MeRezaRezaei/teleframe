<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getInlineGameHighScores (crc32 0f635e1b), returns messages.HighScores. */
final class TlMessagesGetInlineGameHighScoresData extends Data
{
    public const METHOD = 'messages.getInlineGameHighScores';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public mixed $userId,
    ) {
    }
}
