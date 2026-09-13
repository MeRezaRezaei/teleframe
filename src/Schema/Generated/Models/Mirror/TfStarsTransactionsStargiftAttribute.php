<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsTransactionsStargiftAttribute extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_stargift_attributes';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'crafted' => 'boolean',
        'backdrop_id' => 'integer',
        'center_color' => 'integer',
        'edge_color' => 'integer',
        'pattern_color' => 'integer',
        'text_color' => 'integer',
        'sender_id_id' => 'integer',
        'recipient_id_id' => 'integer',
        'date' => 'integer',
    ];
}
