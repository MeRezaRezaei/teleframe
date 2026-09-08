<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsAbsValueAndPrevStatsAbsValueAndPrev (statsAbsValueAndPrev). */
final class TlStatsAbsValueAndPrevStatsAbsValueAndPrevFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsAbsValueAndPrevStatsAbsValueAndPrev> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsAbsValueAndPrevStatsAbsValueAndPrev::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_current' => 0.1,
            'previous' => 0.2,
        ];
    }
}
