<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountAutoSaveSettingsAutoSaveSettings (account.autoSaveSettings). */
final class TlAccountAutoSaveSettingsAutoSaveSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoSaveSettingsAutoSaveSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'users_settings' => 1001,
            'chats_settings' => 1002,
            'broadcasts_settings' => 1003,
        ];
    }
}
