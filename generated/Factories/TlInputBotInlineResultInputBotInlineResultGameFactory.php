<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotInlineResultInputBotInlineResultGame (inputBotInlineResultGame). */
final class TlInputBotInlineResultInputBotInlineResultGameFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultGame> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultGame::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 'id-1',
            'short_name' => 'short_name-2',
            'send_message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
