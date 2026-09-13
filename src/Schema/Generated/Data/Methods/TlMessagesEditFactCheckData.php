<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editFactCheck (crc32 0589ee75), returns Updates. */
final class TlMessagesEditFactCheckData extends Data
{
    public const METHOD = 'messages.editFactCheck';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public mixed $text,
    ) {
    }
}
