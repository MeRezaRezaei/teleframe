<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfStarsTransaction extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'date' => 'integer',
        'refund' => 'boolean',
        'pending' => 'boolean',
        'failed' => 'boolean',
        'gift' => 'boolean',
        'reaction' => 'boolean',
        'stargift_upgrade' => 'boolean',
        'business_transfer' => 'boolean',
        'stargift_resale' => 'boolean',
        'posts_search' => 'boolean',
        'stargift_prepaid_upgrade' => 'boolean',
        'stargift_drop_original_details' => 'boolean',
        'phonegroup_message' => 'boolean',
        'stargift_auction_bid' => 'boolean',
        'offer' => 'boolean',
    ];
}
