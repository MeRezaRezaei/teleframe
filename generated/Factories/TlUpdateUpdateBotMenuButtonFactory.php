<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateBotMenuButton (updateBotMenuButton). */
final class TlUpdateUpdateBotMenuButtonFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotMenuButton> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotMenuButton::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'bot_id' => 1001,
            'button' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
