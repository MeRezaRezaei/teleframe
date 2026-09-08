<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdatesDifferenceDifference (updates.difference). */
final class TlUpdatesDifferenceDifferenceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifference> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifference::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'state' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
