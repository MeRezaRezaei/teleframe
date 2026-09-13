<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsActionParticipantVideo;

class TfAdminLogEventsActionParticipantVideoFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsActionParticipantVideo::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'paused' => fake()->boolean(),
            'endpoint' => fake()->word(),
            'audio_source' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
