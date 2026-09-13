<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for mediaAreaCoordinates of MediaAreaCoordinates.
 */
final class MediaAreaCoordinatesData extends TlMediaAreaCoordinatesAbstractData
{
    public function __construct(
    public int $flags,
    public float $x,
    public float $y,
    public float $w,
    public float $h,
    public float $rotation,
    public ?float $radius,
    ) {
    }
}
