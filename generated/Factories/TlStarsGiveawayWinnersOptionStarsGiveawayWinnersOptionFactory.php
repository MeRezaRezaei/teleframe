<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarsGiveawayWinnersOptionStarsGiveawayWinnersOption (starsGiveawayWinnersOption). */
final class TlStarsGiveawayWinnersOptionStarsGiveawayWinnersOptionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsGiveawayWinnersOptionStarsGiveawayWinnersOption> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsGiveawayWinnersOptionStarsGiveawayWinnersOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_default' => true,
            'users' => 3,
            'per_user_stars' => 1004,
        ];
    }
}
