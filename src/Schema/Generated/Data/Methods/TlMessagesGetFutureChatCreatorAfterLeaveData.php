<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getFutureChatCreatorAfterLeave (crc32 3b7d0ea6), returns User. */
final class TlMessagesGetFutureChatCreatorAfterLeaveData extends Data
{
    public const METHOD = 'messages.getFutureChatCreatorAfterLeave';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
