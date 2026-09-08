<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setDefaultReaction (crc32 4f47a016), returns Bool. */
final class TlMessagesSetDefaultReactionData extends Data
{
    public const METHOD = 'messages.setDefaultReaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $reaction,
    ) {
    }
}
