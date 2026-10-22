<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getSavedRingtones (crc32 e1902288), returns account.SavedRingtones. */
final class TlAccountGetSavedRingtonesData extends Data
{
    public const METHOD = 'account.getSavedRingtones';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
