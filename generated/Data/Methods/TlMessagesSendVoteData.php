<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendVote (crc32 10ea6184), returns Updates. */
final class TlMessagesSendVoteData extends Data
{
    public const METHOD = 'messages.sendVote';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public array $options,
    ) {
    }
}
