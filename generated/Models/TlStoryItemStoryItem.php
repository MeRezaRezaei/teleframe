<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItemEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItemMedia_areas;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItemPrivacy;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItemAlbums;

/** Constructor model for storyItem of StoryItem (crc32 16a4b93c). */
final class TlStoryItemStoryItem extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_story_item_story_item';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pinned' => 'bool',
        'public' => 'bool',
        'close_friends' => 'bool',
        'min' => 'bool',
        'noforwards' => 'bool',
        'edited' => 'bool',
        'contacts' => 'bool',
        'selected_contacts' => 'bool',
        'out' => 'bool',
        'tl_id' => 'int',
        'date' => 'int',
        'from_id' => 'string',
        'fwd_from' => 'string',
        'expire_date' => 'int',
        'caption' => 'string',
        'media' => 'string',
        'views' => 'string',
        'sent_reaction' => 'string',
        'music' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlStoryItemStoryItemEntities::class);
    }
    public function mediaAreas(): HasMany
    {
        return $this->tlChild(TlStoryItemStoryItemMedia_areas::class);
    }
    public function privacy(): HasMany
    {
        return $this->tlChild(TlStoryItemStoryItemPrivacy::class);
    }
    public function albums(): HasMany
    {
        return $this->tlChild(TlStoryItemStoryItemAlbums::class);
    }
}
