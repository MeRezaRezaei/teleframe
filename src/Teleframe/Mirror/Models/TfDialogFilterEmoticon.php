<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * NF5 dialog filter emoticon fact — table tf_dialog_filters_emoticon. Real PK
 * (account_id, id), so `id` IS unique per parent filter and is a faithful
 * scalar primaryKey; AccountScoped guards the account half. 1:1 fact: a row
 * exists only when the filter carries an emoticon.
 */
final class TfDialogFilterEmoticon extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_dialog_filters_emoticon';

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