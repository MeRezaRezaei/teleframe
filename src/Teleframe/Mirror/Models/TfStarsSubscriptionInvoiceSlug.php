<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsSubscriptionInvoiceSlug extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_subscriptions_invoice_slug';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TfStarsSubscription::class, 'id', 'id');
    }
}
