<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfChannelParticipantsSubscriptionUntilDate extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channel_participants_subscription_until_date';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'user_id' => 'integer',
        'subscription_until_date' => 'integer',
    ];
}
