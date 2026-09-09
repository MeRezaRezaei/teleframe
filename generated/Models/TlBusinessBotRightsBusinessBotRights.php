<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for businessBotRights of BusinessBotRights (crc32 a0624cf7). */
final class TlBusinessBotRightsBusinessBotRights extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_bot_rights_business_bot_rights';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'reply' => 'bool',
        'read_messages' => 'bool',
        'delete_sent_messages' => 'bool',
        'delete_received_messages' => 'bool',
        'edit_name' => 'bool',
        'edit_bio' => 'bool',
        'edit_profile_photo' => 'bool',
        'edit_username' => 'bool',
        'view_gifts' => 'bool',
        'sell_gifts' => 'bool',
        'change_gift_settings' => 'bool',
        'transfer_and_upgrade_gifts' => 'bool',
        'transfer_stars' => 'bool',
        'manage_stories' => 'bool',
    ];
}
