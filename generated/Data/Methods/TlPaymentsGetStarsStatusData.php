<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsStatus (crc32 4ea9b3bf), returns payments.StarsStatus. */
final class TlPaymentsGetStarsStatusData extends Data
{
    public const METHOD = 'payments.getStarsStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $ton,
    public mixed $peer,
    ) {
    }
}
