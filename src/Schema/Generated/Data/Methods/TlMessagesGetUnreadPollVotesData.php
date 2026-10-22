<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getUnreadPollVotes (crc32 43286cf2), returns messages.Messages. */
final class TlMessagesGetUnreadPollVotesData extends Data
{
    public const METHOD = 'messages.getUnreadPollVotes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $topMsgId,
    public int $offsetId,
    public int $addOffset,
    public int $limit,
    public int $maxId,
    public int $minId,
    ) {
    }
}
