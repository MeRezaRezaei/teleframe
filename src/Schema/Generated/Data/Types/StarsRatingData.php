<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starsRating of StarsRating.
 */
final class StarsRatingData extends TlStarsRatingAbstractData
{
    public function __construct(
    public int $flags,
    public int $level,
    public int $currentLevelStars,
    public int $stars,
    public ?int $nextLevelStars,
    ) {
    }
}
