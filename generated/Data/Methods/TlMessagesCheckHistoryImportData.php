<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.checkHistoryImport (crc32 43fe19f3), returns messages.HistoryImportParsed. */
final class TlMessagesCheckHistoryImportData extends Data
{
    public const METHOD = 'messages.checkHistoryImport';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $importHead,
    ) {
    }
}
