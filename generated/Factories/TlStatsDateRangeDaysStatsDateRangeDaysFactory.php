<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsDateRangeDaysStatsDateRangeDays (statsDateRangeDays). */
final class TlStatsDateRangeDaysStatsDateRangeDaysFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsDateRangeDaysStatsDateRangeDays> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsDateRangeDaysStatsDateRangeDays::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'min_date' => 1,
            'max_date' => 2,
        ];
    }
}
