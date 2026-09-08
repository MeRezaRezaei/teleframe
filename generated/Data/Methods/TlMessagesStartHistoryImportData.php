<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.startHistoryImport (crc32 b43df344), returns Bool. */
final class TlMessagesStartHistoryImportData extends Data
{
    public const METHOD = 'messages.startHistoryImport';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $importId,
    ) {
    }
}
