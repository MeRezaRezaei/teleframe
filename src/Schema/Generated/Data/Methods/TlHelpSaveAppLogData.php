<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.saveAppLog (crc32 6f02f748), returns Bool. */
final class TlHelpSaveAppLogData extends Data
{
    public const METHOD = 'help.saveAppLog';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $events,
    ) {
    }
}
