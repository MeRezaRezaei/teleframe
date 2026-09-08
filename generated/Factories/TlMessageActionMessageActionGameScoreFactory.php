<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionGameScore (messageActionGameScore). */
final class TlMessageActionMessageActionGameScoreFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGameScore> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGameScore::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'game_id' => 1001,
            'score' => 2,
        ];
    }
}
