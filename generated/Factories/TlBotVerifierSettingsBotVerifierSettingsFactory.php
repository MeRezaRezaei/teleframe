<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotVerifierSettingsBotVerifierSettings (botVerifierSettings). */
final class TlBotVerifierSettingsBotVerifierSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotVerifierSettingsBotVerifierSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotVerifierSettingsBotVerifierSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'can_modify_custom_description' => true,
            'icon' => 1003,
            'company' => 'company-4',
            'custom_description' => 'custom_description-5',
        ];
    }
}
