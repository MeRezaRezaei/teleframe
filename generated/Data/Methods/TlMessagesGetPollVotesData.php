<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPollVotes (crc32 b86e380e), returns messages.VotesList. */
final class TlMessagesGetPollVotesData extends Data
{
    public const METHOD = 'messages.getPollVotes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $id,
    public ?string $option,
    public ?string $offset,
    public int $limit,
    ) {
    }
}
