<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.initHistoryImport (crc32 34090c3b), returns messages.HistoryImport. */
final class TlMessagesInitHistoryImportData extends Data
{
    public const METHOD = 'messages.initHistoryImport';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $file,
    public int $mediaCount,
    ) {
    }
}
