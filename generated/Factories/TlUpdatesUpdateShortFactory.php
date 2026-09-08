<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdatesUpdateShort (updateShort). */
final class TlUpdatesUpdateShortFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShort> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShort::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'update' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 2,
        ];
    }
}
