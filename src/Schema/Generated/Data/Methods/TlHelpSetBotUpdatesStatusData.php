<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.setBotUpdatesStatus (crc32 ec22cfcd), returns Bool. */
final class TlHelpSetBotUpdatesStatusData extends Data
{
    public const METHOD = 'help.setBotUpdatesStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $pendingUpdatesCount,
    public string $message,
    ) {
    }
}
