<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfMessageMedia extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_message_medias';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'message_media_id' => 'integer',
        'spoiler' => 'boolean',
        'live_photo' => 'boolean',
    ];
}
