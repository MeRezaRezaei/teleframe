<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessagesPeerSettingsPeerSettings (messages.peerSettings). */
final class TlMessagesPeerSettingsPeerSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerSettingsPeerSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerSettingsPeerSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'settings' => 1001,
        ];
    }
}
