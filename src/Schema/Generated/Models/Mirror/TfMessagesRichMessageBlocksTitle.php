<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesRichMessageBlocksTitle extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_rich_message_blocks_title';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'webpage_id' => 'integer',
        'document_id' => 'integer',
        'w' => 'integer',
        'h' => 'integer',
        'user_id' => 'integer',
        'relative' => 'boolean',
        'short_time' => 'boolean',
        'long_time' => 'boolean',
        'short_date' => 'boolean',
        'long_date' => 'boolean',
        'day_of_week' => 'boolean',
        'date' => 'integer',
    ];
}
