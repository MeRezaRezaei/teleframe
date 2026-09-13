<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesReplyMarkupRowsButton extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_markup_rows_buttons';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'requires_password' => 'boolean',
        'same_peer' => 'boolean',
        'button_id' => 'integer',
        'quiz' => 'boolean',
        'user_id' => 'integer',
        'max_quantity' => 'integer',
    ];
}
