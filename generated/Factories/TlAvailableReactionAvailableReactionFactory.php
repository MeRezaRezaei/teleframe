<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAvailableReactionAvailableReaction (availableReaction). */
final class TlAvailableReactionAvailableReactionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAvailableReactionAvailableReaction> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAvailableReactionAvailableReaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'inactive' => true,
            'premium' => true,
            'reaction' => 'reaction-4',
            'title' => 'title-5',
            'static_icon' => 1006,
            'appear_animation' => 1007,
            'select_animation' => 1008,
            'activate_animation' => 1009,
            'effect_animation' => 1010,
            'around_animation' => 1011,
            'center_icon' => 1012,
        ];
    }
}
