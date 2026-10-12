<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * NF5 dialog filter colour fact — table tf_dialog_filters_color. Real PK
 * (account_id, id), so `id` IS unique per parent filter and is a faithful
 * scalar primaryKey; AccountScoped guards the account half. `color` is an
 * integer (Telegram colour id), not an enum here. 1:1 fact: a row exists only
 * when the filter carries a colour.
 */
final class TfDialogFilterColor extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_dialog_filters_color';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'color' => 'integer',
    ];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(TfDialogFilter::class, 'id', 'id');
    }
}