<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesPeerStoriesPeerStoriesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesPeerStoriesPeerStoriesUsers;

/** Constructor model for stories.peerStories of stories.PeerStories (crc32 cae68768). */
final class TlStoriesPeerStoriesPeerStories extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stories_peer_stories_peer_stories';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stories' => 'string',
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlStoriesPeerStoriesPeerStoriesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesPeerStoriesPeerStoriesUsers::class);
    }
}
