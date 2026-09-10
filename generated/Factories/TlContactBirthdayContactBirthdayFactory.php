<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlContactBirthdayContactBirthday (contactBirthday). */
final class TlContactBirthdayContactBirthdayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactBirthdayContactBirthday> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactBirthdayContactBirthday::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'contact_id' => 1001,
            'birthday' => 1002,
        ];
    }
}
