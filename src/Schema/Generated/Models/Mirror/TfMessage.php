<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfMessage extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'peer_id_id' => 'integer',
        'date' => 'integer',
        'out' => 'boolean',
        'mentioned' => 'boolean',
        'media_unread' => 'boolean',
        'silent' => 'boolean',
        'post' => 'boolean',
        'from_scheduled' => 'boolean',
        'legacy' => 'boolean',
        'edit_hide' => 'boolean',
        'pinned' => 'boolean',
        'noforwards' => 'boolean',
        'invert_media' => 'boolean',
        'offline' => 'boolean',
        'video_processing_pending' => 'boolean',
        'paid_suggested_post_stars' => 'boolean',
        'paid_suggested_post_ton' => 'boolean',
    ];
}
