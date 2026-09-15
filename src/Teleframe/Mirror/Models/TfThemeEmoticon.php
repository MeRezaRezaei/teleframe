<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_themes — the optional emoticon TEXT fact.
 *
 * @property int $account_id
 * @property int $id
 * @property string $emoticon
 */
final class TfThemeEmoticon extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_themes_emoticon';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(TfTheme::class, 'id', 'id');
    }
}
