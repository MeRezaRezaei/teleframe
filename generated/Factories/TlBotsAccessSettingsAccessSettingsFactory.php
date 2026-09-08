<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotsAccessSettingsAccessSettings (bots.accessSettings). */
final class TlBotsAccessSettingsAccessSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotsAccessSettingsAccessSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotsAccessSettingsAccessSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'restricted' => true,
        ];
    }
}
