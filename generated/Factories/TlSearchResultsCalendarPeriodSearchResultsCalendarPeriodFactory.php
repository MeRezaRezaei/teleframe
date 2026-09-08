<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSearchResultsCalendarPeriodSearchResultsCalendarPeriod (searchResultsCalendarPeriod). */
final class TlSearchResultsCalendarPeriodSearchResultsCalendarPeriodFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSearchResultsCalendarPeriodSearchResultsCalendarPeriod> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSearchResultsCalendarPeriodSearchResultsCalendarPeriod::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'date' => 1,
            'min_msg_id' => 2,
            'max_msg_id' => 3,
            'count' => 4,
        ];
    }
}
