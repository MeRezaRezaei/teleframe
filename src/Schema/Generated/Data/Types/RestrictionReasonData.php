<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for restrictionReason of RestrictionReason.
 */
final class RestrictionReasonData extends TlRestrictionReasonAbstractData
{
    public function __construct(
    public string $platform,
    public string $reason,
    public string $text,
    ) {
    }
}
