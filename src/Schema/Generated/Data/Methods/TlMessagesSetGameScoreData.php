<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setGameScore (crc32 8ef8ecc0), returns Updates. */
final class TlMessagesSetGameScoreData extends Data
{
    public const METHOD = 'messages.setGameScore';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $editMessage,
    public ?bool $force,
    public mixed $peer,
    public int $id,
    public mixed $userId,
    public int $score,
    ) {
    }
}
