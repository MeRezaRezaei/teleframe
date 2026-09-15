<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 message child — single-ctor TextWithEntities flattened to its text;
 * entities (Vector<MessageEntity>) deferred to the messages domain.
 */
final class TfSavedStarGiftMessage extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_message';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [];

    public function savedGift(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGift::class, 'id', 'id');
    }
}
