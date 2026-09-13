<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getMessageReactionsList (crc32 461b3f48), returns messages.MessageReactionsList. */
final class TlMessagesGetMessageReactionsListData extends Data
{
    public const METHOD = 'messages.getMessageReactionsList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $id,
    public mixed $reaction,
    public ?string $offset,
    public int $limit,
    ) {
    }
}
