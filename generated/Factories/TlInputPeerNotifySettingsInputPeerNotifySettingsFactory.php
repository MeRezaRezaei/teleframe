<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputPeerNotifySettingsInputPeerNotifySettings (inputPeerNotifySettings). */
final class TlInputPeerNotifySettingsInputPeerNotifySettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerNotifySettingsInputPeerNotifySettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerNotifySettingsInputPeerNotifySettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'show_previews' => 1002,
            'silent' => 1003,
            'mute_until' => 4,
            'sound' => 1005,
            'stories_muted' => 1006,
            'stories_hide_sender' => 1007,
            'stories_sound' => 1008,
        ];
    }
}
