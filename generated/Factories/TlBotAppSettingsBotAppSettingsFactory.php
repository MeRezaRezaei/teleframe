<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotAppSettingsBotAppSettings (botAppSettings). */
final class TlBotAppSettingsBotAppSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotAppSettingsBotAppSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotAppSettingsBotAppSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'placeholder_path' => 'Ynl0ZXMtMg==',
            'background_color' => 3,
            'background_dark_color' => 4,
            'header_color' => 5,
            'header_dark_color' => 6,
        ];
    }
}
