<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputBotAppInputBotAppShortName (inputBotAppShortName). */
final class TlInputBotAppInputBotAppShortNameFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotAppInputBotAppShortName> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotAppInputBotAppShortName::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'bot_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'short_name' => 'short_name-2',
        ];
    }
}
