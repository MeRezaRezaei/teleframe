<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method updates.getDifference (crc32 19c2f763), returns updates.Difference. */
final class TlUpdatesGetDifferenceData extends Data
{
    public const METHOD = 'updates.getDifference';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public int $pts,
    public ?int $ptsLimit,
    public ?int $ptsTotalLimit,
    public int $date,
    public int $qts,
    public ?int $qtsLimit,
    ) {
    }
}
