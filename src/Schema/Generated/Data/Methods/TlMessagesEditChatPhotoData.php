<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editChatPhoto (crc32 35ddd674), returns Updates. */
final class TlMessagesEditChatPhotoData extends Data
{
    public const METHOD = 'messages.editChatPhoto';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $chatId,
    public mixed $photo,
    ) {
    }
}
