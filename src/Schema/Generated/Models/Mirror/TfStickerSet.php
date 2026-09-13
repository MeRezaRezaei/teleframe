<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfStickerSet extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_sticker_sets';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'access_hash' => 'integer',
        'count' => 'integer',
        'hash' => 'integer',
        'archived' => 'boolean',
        'official' => 'boolean',
        'masks' => 'boolean',
        'emojis' => 'boolean',
        'text_color' => 'boolean',
        'channel_emoji_status' => 'boolean',
        'creator' => 'boolean',
    ];
}
