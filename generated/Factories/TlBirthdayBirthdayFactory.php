<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBirthdayBirthday (birthday). */
final class TlBirthdayBirthdayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBirthdayBirthday> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBirthdayBirthday::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'day' => 2,
            'month' => 3,
            'year' => 4,
        ];
    }
}
