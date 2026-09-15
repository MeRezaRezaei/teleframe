<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N command child — the required Vector<BotCommand> (single-ctor botCommand),
 * one row per vector slot.
 */
final class TfBotInfoCommand extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_commands';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'position' => 'int',
    ];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
