<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesReplyMarkupRowsButtonsStyle extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_markup_rows_buttons_style';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'bg_primary' => 'boolean',
        'bg_danger' => 'boolean',
        'bg_success' => 'boolean',
        'icon' => 'integer',
    ];
}
