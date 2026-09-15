<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N peer_type child — one row per AttachMenuPeerType (five payload-less
 * ctors), constructor discriminator only.
 */
final class TfAttachMenuBotPeerType extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_attach_menu_bots_peer_types';

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
