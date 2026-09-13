<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setChatAvailableReactions (crc32 864b2581), returns Updates. */
final class TlMessagesSetChatAvailableReactionsData extends Data
{
    public const METHOD = 'messages.setChatAvailableReactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public mixed $availableReactions,
    public ?int $reactionsLimit,
    public mixed $paidEnabled,
    ) {
    }
}
