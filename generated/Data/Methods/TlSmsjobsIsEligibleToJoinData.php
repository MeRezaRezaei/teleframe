<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method smsjobs.isEligibleToJoin (crc32 0edc39d0), returns smsjobs.EligibilityToJoin. */
final class TlSmsjobsIsEligibleToJoinData extends Data
{
    public const METHOD = 'smsjobs.isEligibleToJoin';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
