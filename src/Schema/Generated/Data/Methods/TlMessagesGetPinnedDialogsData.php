<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPinnedDialogs (crc32 d6b94df2), returns messages.PeerDialogs. */
final class TlMessagesGetPinnedDialogsData extends Data
{
    public const METHOD = 'messages.getPinnedDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $folderId,
    ) {
    }
}
