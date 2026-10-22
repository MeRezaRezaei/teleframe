<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * NF5 dialog filter title entity (MessageEntity) — table
 * tf_dialog_filters_title_entities. Real PK is composite
 * (account_id, id, position): `id` is NOT unique (one entity row per position
 * of the same title), so `id` is a best-effort scalar stand-in and every
 * query is account-scoped and position-aware. 1:N vector off the filter.
 */
final class TfDialogFilterTitleEntities extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_dialog_filters_title_entities';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
        'offset' => 'integer',
        'length' => 'integer',
        'user_id' => 'integer',
        'document_id' => 'integer',
        'collapsed' => 'boolean',
        'relative' => 'boolean',
        'short_time' => 'boolean',
        'long_time' => 'boolean',
        'short_date' => 'boolean',
        'long_date' => 'boolean',
        'day_of_week' => 'boolean',
        'date' => 'integer',
    ];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(TfDialogFilter::class, 'id', 'id');
    }
}