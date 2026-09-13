<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInlineResultsSendMessageReplyMarkupRowsButtonsPeerTypeBotAdminRight extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'change_info' => 'boolean',
        'post_messages' => 'boolean',
        'edit_messages' => 'boolean',
        'delete_messages' => 'boolean',
        'ban_users' => 'boolean',
        'invite_users' => 'boolean',
        'pin_messages' => 'boolean',
        'add_admins' => 'boolean',
        'anonymous' => 'boolean',
        'manage_call' => 'boolean',
        'other' => 'boolean',
        'manage_topics' => 'boolean',
        'post_stories' => 'boolean',
        'edit_stories' => 'boolean',
        'delete_stories' => 'boolean',
        'manage_direct_messages' => 'boolean',
        'manage_ranks' => 'boolean',
    ];
}
