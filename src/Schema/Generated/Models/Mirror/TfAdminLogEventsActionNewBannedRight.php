<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfAdminLogEventsActionNewBannedRight extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events_action_new_banned_rights';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'view_messages' => 'boolean',
        'send_messages' => 'boolean',
        'send_media' => 'boolean',
        'send_stickers' => 'boolean',
        'send_gifs' => 'boolean',
        'send_games' => 'boolean',
        'send_inline' => 'boolean',
        'embed_links' => 'boolean',
        'send_polls' => 'boolean',
        'change_info' => 'boolean',
        'invite_users' => 'boolean',
        'pin_messages' => 'boolean',
        'manage_topics' => 'boolean',
        'send_photos' => 'boolean',
        'send_videos' => 'boolean',
        'send_roundvideos' => 'boolean',
        'send_audios' => 'boolean',
        'send_voices' => 'boolean',
        'send_docs' => 'boolean',
        'send_plain' => 'boolean',
        'edit_rank' => 'boolean',
        'send_reactions' => 'boolean',
        'until_date' => 'integer',
    ];
}
