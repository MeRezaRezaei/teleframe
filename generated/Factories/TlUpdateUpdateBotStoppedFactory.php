<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotStopped (updateBotStopped). */
final class TlUpdateUpdateBotStoppedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotStopped> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotStopped::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'date' => 2,
            'stopped' => 1003,
            'qts' => 4,
        ];
    }
}
