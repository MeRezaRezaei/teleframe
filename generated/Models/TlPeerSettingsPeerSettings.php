<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for peerSettings of PeerSettings (crc32 f47741f7). */
final class TlPeerSettingsPeerSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_peer_settings_peer_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'report_spam' => 'bool',
        'add_contact' => 'bool',
        'block_contact' => 'bool',
        'share_contact' => 'bool',
        'need_contacts_exception' => 'bool',
        'report_geo' => 'bool',
        'autoarchived' => 'bool',
        'invite_members' => 'bool',
        'request_chat_broadcast' => 'bool',
        'business_bot_paused' => 'bool',
        'business_bot_can_reply' => 'bool',
        'geo_distance' => 'int',
        'request_chat_title' => 'string',
        'request_chat_date' => 'int',
        'business_bot_id' => 'int',
        'business_bot_manage_url' => 'string',
        'charge_paid_message_stars' => 'int',
        'registration_month' => 'string',
        'phone_country' => 'string',
        'name_change_date' => 'int',
        'photo_change_date' => 'int',
    ];
}
