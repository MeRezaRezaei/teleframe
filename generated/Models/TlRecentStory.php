<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

/** Anchor model for TL type RecentStory (spec §4.1). */
final class TlRecentStory extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_recent_story';

    protected $guarded = [];

    public function storiesMaxId(): HasMany
    {
        return $this->hasMany(TlUserUser::class, 'stories_max_id');
    }
    public function storiesMaxIdChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'stories_max_id');
    }
}
