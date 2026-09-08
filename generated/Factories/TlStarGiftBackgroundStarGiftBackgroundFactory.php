<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftBackgroundStarGiftBackground (starGiftBackground). */
final class TlStarGiftBackgroundStarGiftBackgroundFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftBackgroundStarGiftBackground> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftBackgroundStarGiftBackground::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'center_color' => 1,
            'edge_color' => 2,
            'text_color' => 3,
        ];
    }
}
