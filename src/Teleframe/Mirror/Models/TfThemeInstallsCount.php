<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_themes — the optional installs_count fact.
 *
 * @property int $account_id
 * @property int $id
 * @property int $installs_count
 */
final class TfThemeInstallsCount extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_themes_installs_count';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'installs_count' => 'int',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(TfTheme::class, 'id', 'id');
    }
}
