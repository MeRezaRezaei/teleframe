<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editExportedChatInvite (crc32 bdca2f75), returns messages.ExportedChatInvite. */
final class TlMessagesEditExportedChatInviteData extends Data
{
    public const METHOD = 'messages.editExportedChatInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $revoked,
    public mixed $peer,
    public string $link,
    public ?int $expireDate,
    public ?int $usageLimit,
    public mixed $requestNeeded,
    public ?string $title,
    ) {
    }
}
