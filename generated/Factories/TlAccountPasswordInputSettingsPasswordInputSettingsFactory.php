<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountPasswordInputSettingsPasswordInputSettings (account.passwordInputSettings). */
final class TlAccountPasswordInputSettingsPasswordInputSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordInputSettingsPasswordInputSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordInputSettingsPasswordInputSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'new_algo' => 1002,
            'new_password_hash' => 'Ynl0ZXMtMw==',
            'hint' => 'hint-4',
            'email' => 'email-5',
            'new_secure_settings' => 1006,
        ];
    }
}
