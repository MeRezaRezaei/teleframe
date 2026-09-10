<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPeerNotifySettingsPeerNotifySettings (peerNotifySettings). */
final class TlPeerNotifySettingsPeerNotifySettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettingsPeerNotifySettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettingsPeerNotifySettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'show_previews' => 1002,
            'silent' => 1003,
            'mute_until' => 4,
            'ios_sound' => 1005,
            'android_sound' => 1006,
            'other_sound' => 1007,
            'stories_muted' => 1008,
            'stories_hide_sender' => 1009,
            'stories_ios_sound' => 1010,
            'stories_android_sound' => 1011,
            'stories_other_sound' => 1012,
        ];
    }
}
