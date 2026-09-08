<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageRangeMessageRange (messageRange). */
final class TlMessageRangeMessageRangeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageRangeMessageRange> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageRangeMessageRange::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'min_id' => 1,
            'max_id' => 2,
        ];
    }
}
