<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAutoSaveSettingsAutoSaveSettings (autoSaveSettings). */
final class TlAutoSaveSettingsAutoSaveSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoSaveSettingsAutoSaveSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAutoSaveSettingsAutoSaveSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'photos' => true,
            'videos' => true,
            'video_max_size' => 1004,
        ];
    }
}
