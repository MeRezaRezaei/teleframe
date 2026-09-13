<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfStarsSubscription extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_stars_subscriptions';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'until_date' => 'integer',
        'canceled' => 'boolean',
        'can_refulfill' => 'boolean',
        'missing_balance' => 'boolean',
        'bot_canceled' => 'boolean',
    ];
}
