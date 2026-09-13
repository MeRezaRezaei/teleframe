<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfUser extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_users';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'self' => 'boolean',
        'contact' => 'boolean',
        'mutual_contact' => 'boolean',
        'deleted' => 'boolean',
        'bot' => 'boolean',
        'bot_chat_history' => 'boolean',
        'bot_nochats' => 'boolean',
        'verified' => 'boolean',
        'restricted' => 'boolean',
        'min' => 'boolean',
        'bot_inline_geo' => 'boolean',
        'support' => 'boolean',
        'scam' => 'boolean',
        'apply_min_photo' => 'boolean',
        'fake' => 'boolean',
        'bot_attach_menu' => 'boolean',
        'premium' => 'boolean',
        'attach_menu_enabled' => 'boolean',
        'bot_can_edit' => 'boolean',
        'close_friend' => 'boolean',
        'stories_hidden' => 'boolean',
        'stories_unavailable' => 'boolean',
        'contact_require_premium' => 'boolean',
        'bot_business' => 'boolean',
        'bot_has_main_app' => 'boolean',
        'bot_forum_view' => 'boolean',
        'bot_forum_can_manage_topics' => 'boolean',
        'bot_can_manage_bots' => 'boolean',
        'bot_guestchat' => 'boolean',
        'bot_guard' => 'boolean',
    ];
}
