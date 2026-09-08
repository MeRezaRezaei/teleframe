<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteExportedChatInvite (crc32 d464a42b), returns Bool. */
final class TlMessagesDeleteExportedChatInviteData extends Data
{
    public const METHOD = 'messages.deleteExportedChatInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $link,
    ) {
    }
}
