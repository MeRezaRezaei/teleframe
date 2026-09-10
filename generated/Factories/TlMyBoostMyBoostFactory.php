<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMyBoostMyBoost (myBoost). */
final class TlMyBoostMyBoostFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMyBoostMyBoost> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMyBoostMyBoost::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'slot' => 2,
            'peer' => 1003,
            'date' => 4,
            'expires' => 5,
            'cooldown_until_date' => 6,
        ];
    }
}
