<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * NF5 dialog filter title (TextWithEntities.text) — table
 * tf_dialog_filters_title. Real PK (account_id, id), so `id` IS unique per
 * parent filter and is a faithful scalar primaryKey; AccountScoped guards the
 * account half. 1:1 fact: a row exists only when the filter carries a title.
 */
final class TfDialogFilterTitle extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_dialog_filters_title';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
    ];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(TfDialogFilter::class, 'id', 'id');
    }
}