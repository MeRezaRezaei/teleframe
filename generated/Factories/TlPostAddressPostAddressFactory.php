<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPostAddressPostAddress (postAddress). */
final class TlPostAddressPostAddressFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPostAddressPostAddress> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPostAddressPostAddress::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'street_line1' => 'street_line1-1',
            'street_line2' => 'street_line2-2',
            'city' => 'city-3',
            'state' => 'state-4',
            'country_iso2' => 'country_iso2-5',
            'post_code' => 'post_code-6',
        ];
    }
}
