<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInlineResultTitle extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_title';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [];

    public function inlineResult(): BelongsTo
    {
        return $this->belongsTo(TfBotInlineResult::class, 'id', 'id');
    }
}
