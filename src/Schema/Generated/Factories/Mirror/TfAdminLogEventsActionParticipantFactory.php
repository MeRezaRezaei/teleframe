<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsActionParticipant;

class TfAdminLogEventsActionParticipantFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsActionParticipant::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'muted' => fake()->boolean(),
            'left' => fake()->boolean(),
            'can_self_unmute' => fake()->boolean(),
            'just_joined' => fake()->boolean(),
            'versioned' => fake()->boolean(),
            'min' => fake()->boolean(),
            'muted_by_you' => fake()->boolean(),
            'volume_by_admin' => fake()->boolean(),
            'self' => fake()->boolean(),
            'video_joined' => fake()->boolean(),
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'active_date' => fake()->numberBetween(0, 2147483647),
            'source' => fake()->numberBetween(0, 2147483647),
            'volume' => fake()->numberBetween(0, 2147483647),
            'about' => fake()->word(),
            'raise_hand_rating' => fake()->unique()->randomNumber(8),
            'paid_stars_total' => fake()->unique()->randomNumber(8),
        ];
    }
}
