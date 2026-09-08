<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editChatDefaultBannedRights (crc32 a5866b41), returns Updates. */
final class TlMessagesEditChatDefaultBannedRightsData extends Data
{
    public const METHOD = 'messages.editChatDefaultBannedRights';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $bannedRights,
    ) {
    }
}
