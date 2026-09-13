<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfBusinessChatLink extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_business_chat_links';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'business_chat_link_id' => 'integer',
        'views' => 'integer',
    ];
}
