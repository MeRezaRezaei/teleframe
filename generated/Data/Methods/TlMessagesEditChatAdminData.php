<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editChatAdmin (crc32 a85bd1c2), returns Bool. */
final class TlMessagesEditChatAdminData extends Data
{
    public const METHOD = 'messages.editChatAdmin';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $chatId,
    public mixed $userId,
    public mixed $isAdmin,
    ) {
    }
}
