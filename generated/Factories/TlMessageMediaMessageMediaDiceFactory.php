<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaDice (messageMediaDice). */
final class TlMessageMediaMessageMediaDiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_value' => 2,
            'emoticon' => 'emoticon-3',
            'game_outcome' => 1004,
        ];
    }
}
