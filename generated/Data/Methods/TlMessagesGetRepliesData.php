<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getReplies (crc32 22ddd30c), returns messages.Messages. */
final class TlMessagesGetRepliesData extends Data
{
    public const METHOD = 'messages.getReplies';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public int $offsetId,
    public int $offsetDate,
    public int $addOffset,
    public int $limit,
    public int $maxId,
    public int $minId,
    public int $hash,
    ) {
    }
}
