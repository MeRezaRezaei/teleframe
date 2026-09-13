<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.checkChatInvite (crc32 3eadb1bb), returns ChatInvite. */
final class TlMessagesCheckChatInviteData extends Data
{
    public const METHOD = 'messages.checkChatInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $hash,
    ) {
    }
}
