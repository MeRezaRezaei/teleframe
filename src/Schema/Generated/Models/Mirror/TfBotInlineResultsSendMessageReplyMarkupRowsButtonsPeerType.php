<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInlineResultsSendMessageReplyMarkupRowsButtonsPeerType extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'bot' => 'boolean',
        'premium' => 'boolean',
        'creator' => 'boolean',
        'bot_participant' => 'boolean',
        'has_username' => 'boolean',
        'forum' => 'boolean',
        'bot_managed' => 'boolean',
    ];
}
