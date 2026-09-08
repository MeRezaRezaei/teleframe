<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBotInfoBotInfo (botInfo). */
final class TlBotInfoBotInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'has_preview_medias' => true,
            'user_id' => 1003,
            'description' => 'description-4',
            'description_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'description_document' => (string) new \Symfony\Component\Uid\UuidV7(),
            'menu_button' => (string) new \Symfony\Component\Uid\UuidV7(),
            'privacy_policy_url' => 'privacy_policy_url-8',
            'app_settings' => (string) new \Symfony\Component\Uid\UuidV7(),
            'verifier_settings' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
