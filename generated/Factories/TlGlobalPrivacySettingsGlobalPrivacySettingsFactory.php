<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGlobalPrivacySettingsGlobalPrivacySettings (globalPrivacySettings). */
final class TlGlobalPrivacySettingsGlobalPrivacySettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGlobalPrivacySettingsGlobalPrivacySettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGlobalPrivacySettingsGlobalPrivacySettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'archive_and_mute_new_noncontact_peers' => true,
            'keep_archived_unmuted' => true,
            'keep_archived_folders' => true,
            'hide_read_marks' => true,
            'new_noncontact_peers_require_premium' => true,
            'display_gifts_button' => true,
            'noncontact_peers_paid_stars' => 1008,
            'disallowed_gifts' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
