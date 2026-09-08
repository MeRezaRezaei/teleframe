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
            'static_icon' => (string) new \Symfony\Component\Uid\UuidV7(),
            'appear_animation' => (string) new \Symfony\Component\Uid\UuidV7(),
            'select_animation' => (string) new \Symfony\Component\Uid\UuidV7(),
            'activate_animation' => (string) new \Symfony\Component\Uid\UuidV7(),
            'effect_animation' => (string) new \Symfony\Component\Uid\UuidV7(),
            'around_animation' => (string) new \Symfony\Component\Uid\UuidV7(),
            'center_icon' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
