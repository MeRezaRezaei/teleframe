<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlConfigConfig (config). */
final class TlConfigConfigFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConfigConfig> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConfigConfig::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'default_p2p_contacts' => true,
            'preload_featured_stickers' => true,
            'revoke_pm_inbox' => true,
            'blocked_mode' => true,
            'force_try_ipv6' => true,
            'date' => 7,
            'expires' => 8,
            'test_mode' => (string) new \Symfony\Component\Uid\UuidV7(),
            'this_dc' => 10,
            'dc_txt_domain_name' => 'dc_txt_domain_name-11',
            'chat_size_max' => 12,
            'megagroup_size_max' => 13,
            'forwarded_count_max' => 14,
            'online_update_period_ms' => 15,
            'offline_blur_timeout_ms' => 16,
            'offline_idle_timeout_ms' => 17,
            'online_cloud_timeout_ms' => 18,
            'notify_cloud_delay_ms' => 19,
            'notify_default_delay_ms' => 20,
            'push_chat_period_ms' => 21,
            'push_chat_limit' => 22,
            'edit_time_limit' => 23,
            'revoke_time_limit' => 24,
            'revoke_pm_time_limit' => 25,
            'rating_e_decay' => 26,
            'stickers_recent_limit' => 27,
            'channels_read_media_period' => 28,
            'tmp_sessions' => 29,
            'call_receive_timeout_ms' => 30,
            'call_ring_timeout_ms' => 31,
            'call_connect_timeout_ms' => 32,
            'call_packet_timeout_ms' => 33,
            'me_url_prefix' => 'me_url_prefix-34',
            'autoupdate_url_prefix' => 'autoupdate_url_prefix-35',
            'gif_search_username' => 'gif_search_username-36',
            'venue_search_username' => 'venue_search_username-37',
            'img_search_username' => 'img_search_username-38',
            'static_maps_provider' => 'static_maps_provider-39',
            'caption_length_max' => 40,
            'message_length_max' => 41,
            'webfile_dc_id' => 42,
            'suggested_lang_code' => 'suggested_lang_code-43',
            'lang_pack_version' => 44,
            'base_lang_pack_version' => 45,
            'reactions_default' => (string) new \Symfony\Component\Uid\UuidV7(),
            'autologin_token' => 'autologin_token-47',
        ];
    }
}
