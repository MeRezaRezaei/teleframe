<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesPeerStoriesPeerStories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type PeerStories (spec §4.1). */
final class TlPeerStories extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_peer_stories';

    protected $guarded = [];

    public function stories(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'stories');
    }
    public function storiesStoriesPeerStories(): HasMany
    {
        return $this->hasMany(TlStoriesPeerStoriesPeerStories::class, 'stories');
    }
    public function storiesUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'stories');
    }
}
