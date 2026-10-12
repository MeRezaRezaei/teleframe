<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.readEncryptedHistory (crc32 7f4b690a), returns Bool. */
final class TlMessagesReadEncryptedHistoryData extends Data
{
    public const METHOD = 'messages.readEncryptedHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $maxDate,
    ) {
    }
}
