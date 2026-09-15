<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 photo child — the flag-gated WebDocument, flattened to its immediate
 * scalar fields.
 */
final class TfStarsSubscriptionPhoto extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_subscriptions_photo';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'access_hash' => 'int',
        'size' => 'int',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TfStarsSubscription::class, 'id', 'id');
    }
}
