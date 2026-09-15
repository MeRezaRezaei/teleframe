<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 document child — the flag-gated Document union (documentEmpty |
 * document), flattened; the document's inner id collides → dropped.
 */
final class TfBotAppDocument extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_apps_document';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'access_hash' => 'int',
        'date' => 'int',
        'size' => 'int',
        'dc_id' => 'int',
    ];

    public function botApp(): BelongsTo
    {
        return $this->belongsTo(TfBotApp::class, 'id', 'id');
    }
}
