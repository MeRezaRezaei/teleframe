<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages holding the scalar from_boosts_applied count.
 */
final class TfMessageFromBoostsApplied extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_messages_from_boosts_applied';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'from_boosts_applied' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}