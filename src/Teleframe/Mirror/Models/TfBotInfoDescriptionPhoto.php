<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 description_photo child — the flag-gated Photo union, flattened.
 */
final class TfBotInfoDescriptionPhoto extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_description_photo';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'access_hash' => 'int',
        'date' => 'int',
        'dc_id' => 'int',
        'has_stickers' => 'bool',
    ];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
