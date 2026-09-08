<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getChatInviteImporters (crc32 df04dd4e), returns messages.ChatInviteImporters. */
final class TlMessagesGetChatInviteImportersData extends Data
{
    public const METHOD = 'messages.getChatInviteImporters';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $requested,
    public ?bool $subscriptionExpired,
    public mixed $peer,
    public ?string $link,
    public ?string $q,
    public int $offsetDate,
    public mixed $offsetUser,
    public int $limit,
    ) {
    }
}
