<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages holding the scalar grouped_id (album grouping key).
 */
final class TfMessageGroupedId extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_messages_grouped_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'grouped_id' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}