<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for future_salts of FutureSalts.
 */
final class FutureSaltsData extends TlFutureSaltsAbstractData
{
    public function __construct(
    public int $reqMsgId,
    public int $now,
    public array $salts,
    ) {
    }
}
