<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfUsersEmojiStatu extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_emoji_status';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'document_id' => 'integer',
        'until' => 'integer',
        'collectible_id' => 'integer',
        'pattern_document_id' => 'integer',
        'center_color' => 'integer',
        'edge_color' => 'integer',
        'pattern_color' => 'integer',
        'text_color' => 'integer',
    ];
}
