<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 bot_payload child — the flag.7?bytes payload stored as lowercase hex.
 */
final class TfStarsTransactionBotPayload extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_bot_payload';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
