<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBusinessWeeklyOpenBusinessWeeklyOpen (businessWeeklyOpen). */
final class TlBusinessWeeklyOpenBusinessWeeklyOpenFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessWeeklyOpenBusinessWeeklyOpen> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessWeeklyOpenBusinessWeeklyOpen::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'start_minute' => 1,
            'end_minute' => 2,
        ];
    }
}
