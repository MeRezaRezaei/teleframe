<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockMap (pageBlockMap). */
final class TlPageBlockPageBlockMapFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockMap> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockMap::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'geo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'zoom' => 2,
            'w' => 3,
            'h' => 4,
            'caption' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
