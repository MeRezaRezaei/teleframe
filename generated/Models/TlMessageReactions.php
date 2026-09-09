<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageService;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateMessageReactions;

/** Anchor model for TL type MessageReactions (spec §4.1). */
final class TlMessageReactions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_reactions';

    protected $guarded = [];

    public function reactions(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'reactions');
    }
    public function reactionsMessageService(): HasMany
    {
        return $this->hasMany(TlMessageMessageService::class, 'reactions');
    }
    public function reactionsUpdateMessageReactions(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateMessageReactions::class, 'reactions');
    }
}
