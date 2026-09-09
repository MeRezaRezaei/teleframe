<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotMessageReactionNew_reactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotMessageReactionOld_reactions;

/** Constructor model for updateBotMessageReaction of Update (crc32 ac21d3ce). */
final class TlUpdateUpdateBotMessageReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_bot_message_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
        'date' => 'int',
        'qts' => 'int',
    ];

    public function oldReactions(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateBotMessageReactionOld_reactions::class);
    }
    public function newReactions(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateBotMessageReactionNew_reactions::class);
    }
}
