<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaInputMediaAreaChannelPost;

/** Anchor model for TL type InputChannel (spec §4.1). */
final class TlInputChannel extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_channel_input_channel';

    protected $guarded = [];

    public function channel(): HasMany
    {
        return $this->hasMany(TlMediaAreaInputMediaAreaChannelPost::class, 'channel');
    }
    public function migratedTo(): HasMany
    {
        return $this->hasMany(TlChatChat::class, 'migrated_to');
    }
}
