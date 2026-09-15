<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 photo child — the required Photo union (photoEmpty | photo), flattened;
 * the photo's inner id collides with the parent key → dropped.
 */
final class TfBotAppPhoto extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_apps_photo';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'access_hash' => 'int',
        'date' => 'int',
        'dc_id' => 'int',
        'has_stickers' => 'bool',
    ];

    public function botApp(): BelongsTo
    {
        return $this->belongsTo(TfBotApp::class, 'id', 'id');
    }
}
