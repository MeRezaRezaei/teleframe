<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBusinessLocationBusinessLocation (businessLocation). */
final class TlBusinessLocationBusinessLocationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessLocationBusinessLocation> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessLocationBusinessLocation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'geo_point' => (string) new \Symfony\Component\Uid\UuidV7(),
            'address' => 'address-3',
        ];
    }
}
