<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.exportChatInvite (crc32 a455de90), returns ExportedChatInvite. */
final class TlMessagesExportChatInviteData extends Data
{
    public const METHOD = 'messages.exportChatInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $legacyRevokePermanent,
    public ?bool $requestNeeded,
    public mixed $peer,
    public ?int $expireDate,
    public ?int $usageLimit,
    public ?string $title,
    public mixed $subscriptionPricing,
    ) {
    }
}
