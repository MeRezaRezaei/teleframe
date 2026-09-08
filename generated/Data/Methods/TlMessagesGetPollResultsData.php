<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPollResults (crc32 eda3e33b), returns Updates. */
final class TlMessagesGetPollResultsData extends Data
{
    public const METHOD = 'messages.getPollResults';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public int $pollHash,
    ) {
    }
}
