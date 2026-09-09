<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateUserEmojiStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

/** Anchor model for TL type EmojiStatus (spec §4.1). */
final class TlEmojiStatus extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_emoji_status';

    protected $guarded = [];

    public function emojiStatus(): HasMany
    {
        return $this->hasMany(TlUserUser::class, 'emoji_status');
    }
    public function emojiStatusChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'emoji_status');
    }
    public function emojiStatusUpdateUserEmojiStatus(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateUserEmojiStatus::class, 'emoji_status');
    }
    public function newValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStatus::class, 'new_value');
    }
    public function prevValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeEmojiStatus::class, 'prev_value');
    }
}
