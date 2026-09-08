<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAccountAutoDownloadSettingsAutoDownloadSettings (account.autoDownloadSettings). */
final class TlAccountAutoDownloadSettingsAutoDownloadSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoDownloadSettingsAutoDownloadSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoDownloadSettingsAutoDownloadSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'low' => (string) new \Symfony\Component\Uid\UuidV7(),
            'medium' => (string) new \Symfony\Component\Uid\UuidV7(),
            'high' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
