<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsTransactionsExtendedMediaPoll extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_extended_media_poll';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'closed' => 'boolean',
        'public_voters' => 'boolean',
        'multiple_choice' => 'boolean',
        'quiz' => 'boolean',
        'open_answers' => 'boolean',
        'revoting_disabled' => 'boolean',
        'shuffle_answers' => 'boolean',
        'hide_results_until_close' => 'boolean',
        'creator' => 'boolean',
        'subscribers_only' => 'boolean',
        'close_period' => 'integer',
        'close_date' => 'integer',
        'hash' => 'integer',
    ];
}
