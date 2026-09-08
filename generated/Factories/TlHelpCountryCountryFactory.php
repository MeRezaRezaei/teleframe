<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHelpCountryCountry (help.country). */
final class TlHelpCountryCountryFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpCountryCountry> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpCountryCountry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'hidden' => true,
            'iso2' => 'iso2-3',
            'default_name' => 'default_name-4',
            'name' => 'name-5',
        ];
    }
}
