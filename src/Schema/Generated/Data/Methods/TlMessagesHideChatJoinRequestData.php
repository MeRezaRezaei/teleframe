<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.hideChatJoinRequest (crc32 7fe7e815), returns Updates. */
final class TlMessagesHideChatJoinRequestData extends Data
{
    public const METHOD = 'messages.hideChatJoinRequest';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $approved,
    public mixed $peer,
    public mixed $userId,
    ) {
    }
}
