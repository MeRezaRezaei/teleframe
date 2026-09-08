<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdatesDifferenceDifferenceSlice (updates.differenceSlice). */
final class TlUpdatesDifferenceDifferenceSliceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSlice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSlice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'intermediate_state' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
