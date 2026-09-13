<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editChatCreator (crc32 f743b857), returns Updates. */
final class TlMessagesEditChatCreatorData extends Data
{
    public const METHOD = 'messages.editChatCreator';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $userId,
    public mixed $password,
    ) {
    }
}
