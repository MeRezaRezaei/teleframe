<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputThemeSettingsInputThemeSettings (inputThemeSettings). */
final class TlInputThemeSettingsInputThemeSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputThemeSettingsInputThemeSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputThemeSettingsInputThemeSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'message_colors_animated' => true,
            'base_theme' => 1003,
            'accent_color' => 4,
            'outbox_accent_color' => 5,
            'wallpaper' => 1006,
            'wallpaper_settings' => 1007,
        ];
    }
}
