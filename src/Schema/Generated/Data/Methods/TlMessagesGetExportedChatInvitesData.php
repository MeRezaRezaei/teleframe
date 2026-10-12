<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getExportedChatInvites (crc32 a2b5a3f6), returns messages.ExportedChatInvites. */
final class TlMessagesGetExportedChatInvitesData extends Data
{
    public const METHOD = 'messages.getExportedChatInvites';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $revoked,
    public mixed $peer,
    public mixed $adminId,
    public ?int $offsetDate,
    public ?string $offsetLink,
    public int $limit,
    ) {
    }
}
