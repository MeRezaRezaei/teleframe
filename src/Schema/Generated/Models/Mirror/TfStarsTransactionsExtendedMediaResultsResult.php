<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsTransactionsExtendedMediaResultsResult extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_extended_media_results_results';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'chosen' => 'boolean',
        'correct' => 'boolean',
        'voters' => 'integer',
        'recent_voters_id' => 'integer',
    ];
}
