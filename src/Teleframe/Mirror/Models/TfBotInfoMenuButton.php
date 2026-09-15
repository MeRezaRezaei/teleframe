<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 menu_button child — the BotMenuButton union (botMenuButtonDefault |
 * botMenuButtonCommands | botMenuButton).
 */
final class TfBotInfoMenuButton extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_menu_button';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
