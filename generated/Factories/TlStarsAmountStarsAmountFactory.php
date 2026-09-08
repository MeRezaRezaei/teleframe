<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarsAmountStarsAmount (starsAmount). */
final class TlStarsAmountStarsAmountFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmountStarsAmount> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmountStarsAmount::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'amount' => 1001,
            'nanos' => 2,
        ];
    }
}
