<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getGroupsForDiscussion (crc32 f5dad378), returns messages.Chats. */
final class TlChannelsGetGroupsForDiscussionData extends Data
{
    public const METHOD = 'channels.getGroupsForDiscussion';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
