<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 description_document child — the flag-gated Document union, flattened.
 */
final class TfBotInfoDescriptionDocument extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_description_document';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'access_hash' => 'int',
        'date' => 'int',
        'size' => 'int',
        'dc_id' => 'int',
    ];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
