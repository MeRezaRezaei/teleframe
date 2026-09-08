<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMessageReadMetricInputMessageReadMetric (inputMessageReadMetric). */
final class TlInputMessageReadMetricInputMessageReadMetricFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMessageReadMetricInputMessageReadMetric> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMessageReadMetricInputMessageReadMetric::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'msg_id' => 1,
            'view_id' => 1002,
            'time_in_view_ms' => 3,
            'active_time_in_view_ms' => 4,
            'height_to_viewport_ratio_permille' => 5,
            'seen_range_ratio_permille' => 6,
        ];
    }
}
