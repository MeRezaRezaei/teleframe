<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDiscussionMessage (crc32 446972fd), returns messages.DiscussionMessage. */
final class TlMessagesGetDiscussionMessageData extends Data
{
    public const METHOD = 'messages.getDiscussionMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    ) {
    }
}
