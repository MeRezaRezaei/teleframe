<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAvailableEffectAvailableEffect (availableEffect). */
final class TlAvailableEffectAvailableEffectFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAvailableEffectAvailableEffect> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAvailableEffectAvailableEffect::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'premium_required' => true,
            'tl_id' => 1003,
            'emoticon' => 'emoticon-4',
            'static_icon_id' => 1005,
            'effect_sticker_id' => 1006,
            'effect_animation_id' => 1007,
        ];
    }
}
