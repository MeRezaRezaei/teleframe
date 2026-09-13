<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setTyping (crc32 58943ee2), returns Bool. */
final class TlMessagesSetTypingData extends Data
{
    public const METHOD = 'messages.setTyping';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $topMsgId,
    public mixed $action,
    ) {
    }
}
