<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPeerSettingsPeerSettings (peerSettings). */
final class TlPeerSettingsPeerSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerSettingsPeerSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerSettingsPeerSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'report_spam' => true,
            'add_contact' => true,
            'block_contact' => true,
            'share_contact' => true,
            'need_contacts_exception' => true,
            'report_geo' => true,
            'autoarchived' => true,
            'invite_members' => true,
            'request_chat_broadcast' => true,
            'business_bot_paused' => true,
            'business_bot_can_reply' => true,
            'geo_distance' => 13,
            'request_chat_title' => 'request_chat_title-14',
            'request_chat_date' => 15,
            'business_bot_id' => 1016,
            'business_bot_manage_url' => 'business_bot_manage_url-17',
            'charge_paid_message_stars' => 1018,
            'registration_month' => 'registration_month-19',
            'phone_country' => 'phone_country-20',
            'name_change_date' => 21,
            'photo_change_date' => 22,
        ];
    }
}
