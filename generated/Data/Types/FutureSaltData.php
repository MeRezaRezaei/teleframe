<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for future_salt of FutureSalt.
 */
final class FutureSaltData extends TlFutureSaltAbstractData
{
    public function __construct(
    public int $validSince,
    public int $validUntil,
    public int $salt,
    ) {
    }
}
