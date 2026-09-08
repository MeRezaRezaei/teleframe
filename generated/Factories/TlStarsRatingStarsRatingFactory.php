<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarsRatingStarsRating (starsRating). */
final class TlStarsRatingStarsRatingFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsRatingStarsRating> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsRatingStarsRating::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'level' => 2,
            'current_level_stars' => 1003,
            'stars' => 1004,
            'next_level_stars' => 1005,
        ];
    }
}
