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
            'show_previews' => (string) new \Symfony\Component\Uid\UuidV7(),
            'silent' => (string) new \Symfony\Component\Uid\UuidV7(),
            'mute_until' => 4,
            'sound' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stories_muted' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stories_hide_sender' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stories_sound' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
