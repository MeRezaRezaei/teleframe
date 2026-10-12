<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.addChatUser (crc32 cbc6d107), returns messages.InvitedUsers. */
final class TlMessagesAddChatUserData extends Data
{
    public const METHOD = 'messages.addChatUser';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $chatId,
    public mixed $userId,
    public int $fwdLimit,
    ) {
    }
}
