<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsStarsRevenueStatsStarsRevenueStats (payments.starsRevenueStats). */
final class TlPaymentsStarsRevenueStatsStarsRevenueStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsRevenueStatsStarsRevenueStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsRevenueStatsStarsRevenueStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'top_hours_graph' => 1002,
            'revenue_graph' => 1003,
            'status' => 1004,
            'usd_rate' => 0.5,
        ];
    }
}
