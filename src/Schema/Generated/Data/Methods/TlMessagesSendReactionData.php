<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendReaction (crc32 d30d78d4), returns Updates. */
final class TlMessagesSendReactionData extends Data
{
    public const METHOD = 'messages.sendReaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $big,
    public ?bool $addToRecent,
    public mixed $peer,
    public int $msgId,
    public ?array $reaction,
    ) {
    }
}
