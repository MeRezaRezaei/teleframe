<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPremiumBoostsListBoostsList (premium.boostsList). */
final class TlPremiumBoostsListBoostsListFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsListBoostsList> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsListBoostsList::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'count' => 2,
            'next_offset' => 'next_offset-3',
        ];
    }
}
