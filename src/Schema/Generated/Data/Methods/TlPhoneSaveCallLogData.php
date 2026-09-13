<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.saveCallLog (crc32 41248786), returns Bool. */
final class TlPhoneSaveCallLogData extends Data
{
    public const METHOD = 'phone.saveCallLog';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $file,
    ) {
    }
}
