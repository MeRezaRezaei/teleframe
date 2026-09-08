<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageEntityMessageEntityFormattedDate (messageEntityFormattedDate). */
final class TlMessageEntityMessageEntityFormattedDateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityFormattedDate> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityFormattedDate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'relative' => true,
            'short_time' => true,
            'long_time' => true,
            'short_date' => true,
            'long_date' => true,
            'day_of_week' => true,
            'tl_offset' => 8,
            'length' => 9,
            'date' => 10,
        ];
    }
}
