<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesReplyMarkup extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_markup';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'selective' => 'boolean',
        'single_use' => 'boolean',
        'resize' => 'boolean',
        'persistent' => 'boolean',
    ];
}
