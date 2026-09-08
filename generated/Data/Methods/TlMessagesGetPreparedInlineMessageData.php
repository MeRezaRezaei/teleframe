<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPreparedInlineMessage (crc32 857ebdb8), returns messages.PreparedInlineMessage. */
final class TlMessagesGetPreparedInlineMessageData extends Data
{
    public const METHOD = 'messages.getPreparedInlineMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $id,
    ) {
    }
}
