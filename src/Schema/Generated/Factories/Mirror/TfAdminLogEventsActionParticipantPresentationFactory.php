<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsActionParticipantPresentation;

class TfAdminLogEventsActionParticipantPresentationFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsActionParticipantPresentation::class;

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
