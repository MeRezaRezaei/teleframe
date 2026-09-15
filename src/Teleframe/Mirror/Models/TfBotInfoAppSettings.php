<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 app_settings child — single-ctor botAppSettings; placeholder_path bytes
 * stored as lowercase hex.
 */
final class TfBotInfoAppSettings extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_app_settings';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'background_color' => 'int',
        'background_dark_color' => 'int',
        'header_color' => 'int',
        'header_dark_color' => 'int',
    ];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
