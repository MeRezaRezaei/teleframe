<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.importChatInvite (crc32 de91436e), returns messages.ChatInviteJoinResult. */
final class TlMessagesImportChatInviteData extends Data
{
    public const METHOD = 'messages.importChatInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $hash,
    ) {
    }
}
