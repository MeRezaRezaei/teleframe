<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for highScore of HighScore.
 */
final class HighScoreData extends TlHighScoreAbstractData
{
    public function __construct(
    public int $pos,
    public int $userId,
    public int $score,
    ) {
    }
}
