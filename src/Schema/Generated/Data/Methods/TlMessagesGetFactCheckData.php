<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getFactCheck (crc32 b9cdc5ee), returns Vector<FactCheck>. */
final class TlMessagesGetFactCheckData extends Data
{
    public const METHOD = 'messages.getFactCheck';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public array $msgId,
    ) {
    }
}
