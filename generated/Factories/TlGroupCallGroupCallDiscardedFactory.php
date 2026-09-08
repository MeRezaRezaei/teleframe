<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGroupCallGroupCallDiscarded (groupCallDiscarded). */
final class TlGroupCallGroupCallDiscardedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallGroupCallDiscarded> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallGroupCallDiscarded::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'access_hash' => 1002,
            'duration' => 3,
        ];
    }
}
