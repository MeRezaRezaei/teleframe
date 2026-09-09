<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConfigConfigDc_options;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReaction;

/** Constructor model for config of Config (crc32 cc1a241e). */
final class TlConfigConfig extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_config_config';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'default_p2p_contacts' => 'bool',
        'preload_featured_stickers' => 'bool',
        'revoke_pm_inbox' => 'bool',
        'blocked_mode' => 'bool',
        'force_try_ipv6' => 'bool',
        'date' => 'int',
        'expires' => 'int',
        'this_dc' => 'int',
        'dc_txt_domain_name' => 'string',
        'chat_size_max' => 'int',
        'megagroup_size_max' => 'int',
        'forwarded_count_max' => 'int',
        'online_update_period_ms' => 'int',
        'offline_blur_timeout_ms' => 'int',
        'offline_idle_timeout_ms' => 'int',
        'online_cloud_timeout_ms' => 'int',
        'notify_cloud_delay_ms' => 'int',
        'notify_default_delay_ms' => 'int',
        'push_chat_period_ms' => 'int',
        'push_chat_limit' => 'int',
        'edit_time_limit' => 'int',
        'revoke_time_limit' => 'int',
        'revoke_pm_time_limit' => 'int',
        'rating_e_decay' => 'int',
        'stickers_recent_limit' => 'int',
        'channels_read_media_period' => 'int',
        'tmp_sessions' => 'int',
        'call_receive_timeout_ms' => 'int',
        'call_ring_timeout_ms' => 'int',
        'call_connect_timeout_ms' => 'int',
        'call_packet_timeout_ms' => 'int',
        'me_url_prefix' => 'string',
        'autoupdate_url_prefix' => 'string',
        'gif_search_username' => 'string',
        'venue_search_username' => 'string',
        'img_search_username' => 'string',
        'static_maps_provider' => 'string',
        'caption_length_max' => 'int',
        'message_length_max' => 'int',
        'webfile_dc_id' => 'int',
        'suggested_lang_code' => 'string',
        'lang_pack_version' => 'int',
        'base_lang_pack_version' => 'int',
        'autologin_token' => 'string',
    ];

    public function dcOptions(): HasMany
    {
        return $this->tlChild(TlConfigConfigDc_options::class);
    }

    public function testMode(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'test_mode');
    }
    public function reactionsDefault(): BelongsTo
    {
        return $this->belongsTo(TlReaction::class, 'reactions_default');
    }
}
