<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAttributeStarGiftAttributeBackdrop (starGiftAttributeBackdrop). */
final class TlStarGiftAttributeStarGiftAttributeBackdropFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeBackdrop> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeBackdrop::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'name-1',
            'backdrop_id' => 2,
            'center_color' => 3,
            'edge_color' => 4,
            'pattern_color' => 5,
            'text_color' => 6,
            'rarity' => 1007,
        ];
    }
}
