<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateBusinessWorkHours (crc32 4b00e066), returns Bool. */
final class TlAccountUpdateBusinessWorkHoursData extends Data
{
    public const METHOD = 'account.updateBusinessWorkHours';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $businessWorkHours,
    ) {
    }
}
