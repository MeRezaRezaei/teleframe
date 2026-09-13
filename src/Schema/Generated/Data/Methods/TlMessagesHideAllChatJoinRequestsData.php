<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.hideAllChatJoinRequests (crc32 e085f4ea), returns Updates. */
final class TlMessagesHideAllChatJoinRequestsData extends Data
{
    public const METHOD = 'messages.hideAllChatJoinRequests';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $approved,
    public mixed $peer,
    public ?string $link,
    ) {
    }
}
