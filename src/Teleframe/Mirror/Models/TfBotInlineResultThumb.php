<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 thumb child — the flag-gated WebDocument (webDocument |
 * webDocumentNoProxy), flattened to its immediate scalar fields.
 */
final class TfBotInlineResultThumb extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_thumb';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'access_hash' => 'int',
        'size' => 'int',
    ];

    public function inlineResult(): BelongsTo
    {
        return $this->belongsTo(TfBotInlineResult::class, 'id', 'id');
    }
}
