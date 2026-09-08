<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaGame (messageMediaGame). */
final class TlMessageMediaMessageMediaGameFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGame> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGame::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'game' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
