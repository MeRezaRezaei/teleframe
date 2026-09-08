<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.createChat (crc32 92ceddd4), returns messages.InvitedUsers. */
final class TlMessagesCreateChatData extends Data
{
    public const METHOD = 'messages.createChat';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public array $users,
    public string $title,
    public ?int $ttlPeriod,
    ) {
    }
}
