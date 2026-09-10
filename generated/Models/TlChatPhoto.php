<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFolderFolder;

/** Anchor model for TL type ChatPhoto (spec §4.1). */
final class TlChatPhoto extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_photo_chat_photo';

    protected $guarded = [];

    public function photo(): HasMany
    {
        return $this->hasMany(TlChatChat::class, 'photo');
    }
    public function photoChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'photo');
    }
    public function photoFolder(): HasMany
    {
        return $this->hasMany(TlFolderFolder::class, 'photo');
    }
}
