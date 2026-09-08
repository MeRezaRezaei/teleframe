<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHighScoreHighScore (highScore). */
final class TlHighScoreHighScoreFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHighScoreHighScore> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHighScoreHighScore::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'pos' => 1,
            'user_id' => 1002,
            'score' => 3,
        ];
    }
}
