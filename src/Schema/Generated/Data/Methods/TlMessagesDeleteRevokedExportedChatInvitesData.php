<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteRevokedExportedChatInvites (crc32 56987bd5), returns Bool. */
final class TlMessagesDeleteRevokedExportedChatInvitesData extends Data
{
    public const METHOD = 'messages.deleteRevokedExportedChatInvites';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $adminId,
    ) {
    }
}
