<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdatesUpdatesCombined (updatesCombined). */
final class TlUpdatesUpdatesCombinedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesCombined> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesCombined::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'date' => 1,
            'seq_start' => 2,
            'seq' => 3,
        ];
    }
}
