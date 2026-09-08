<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBusinessBotRightsBusinessBotRights (businessBotRights). */
final class TlBusinessBotRightsBusinessBotRightsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRightsBusinessBotRights> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessBotRightsBusinessBotRights::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'reply' => true,
            'read_messages' => true,
            'delete_sent_messages' => true,
            'delete_received_messages' => true,
            'edit_name' => true,
            'edit_bio' => true,
            'edit_profile_photo' => true,
            'edit_username' => true,
            'view_gifts' => true,
            'sell_gifts' => true,
            'change_gift_settings' => true,
            'transfer_and_upgrade_gifts' => true,
            'transfer_stars' => true,
            'manage_stories' => true,
        ];
    }
}
