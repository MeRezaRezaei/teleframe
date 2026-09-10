<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPageBlockPageBlockDetails (pageBlockDetails). */
final class TlPageBlockPageBlockDetailsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockDetails> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockDetails::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'open' => true,
            'title' => 1003,
        ];
    }
}
