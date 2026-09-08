<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGameGame (game). */
final class TlGameGameFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGameGame> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGameGame::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_id' => 1002,
            'access_hash' => 1003,
            'short_name' => 'short_name-4',
            'title' => 'title-5',
            'description' => 'description-6',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'document' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
