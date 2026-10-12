<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteChatUser (crc32 a2185cab), returns Updates. */
final class TlMessagesDeleteChatUserData extends Data
{
    public const METHOD = 'messages.deleteChatUser';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $revokeHistory,
    public int $chatId,
    public mixed $userId,
    ) {
    }
}
