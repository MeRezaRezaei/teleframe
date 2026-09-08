<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarsTopupOptionStarsTopupOption (starsTopupOption). */
final class TlStarsTopupOptionStarsTopupOptionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTopupOptionStarsTopupOption> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTopupOptionStarsTopupOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'extended' => true,
            'stars' => 1003,
            'store_product' => 'store_product-4',
            'currency' => 'currency-5',
            'amount' => 1006,
        ];
    }
}
