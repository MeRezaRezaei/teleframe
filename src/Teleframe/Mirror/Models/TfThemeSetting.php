<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_themes — the 1:N settings Vector<ThemeSettings>.
 * ThemeSettings union (single ctor) — no constructor discriminator column;
 * scalar payload only (nested BaseTheme / WallPaper / Vector<int> deferred).
 *
 * @property int $account_id
 * @property int $id
 * @property int $position
 * @property bool $message_colors_animated
 * @property int $accent_color
 * @property int $outbox_accent_color
 */
final class TfThemeSetting extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_themes_settings';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'position' => 'int',
        'message_colors_animated' => 'bool',
        'accent_color' => 'int',
        'outbox_accent_color' => 'int',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(TfTheme::class, 'id', 'id');
    }
}
