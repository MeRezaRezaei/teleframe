<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N icon child — one row per attachMenuBotIcon in the vector. The required
 * Document `icon` and Vector<AttachMenuBotIconColor> `colors` are nested
 * objects → deferred.
 */
final class TfAttachMenuBotIcon extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_attach_menu_bots_icons';

    protected $primaryKey = 'bot_id';

    protected $guarded = [];

    protected $casts = [
        'position' => 'int',
    ];

    public function attachMenuBot(): BelongsTo
    {
        return $this->belongsTo(TfAttachMenuBot::class, 'bot_id', 'bot_id');
    }
}
