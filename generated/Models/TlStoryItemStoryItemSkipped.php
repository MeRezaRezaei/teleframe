<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storyItemSkipped of StoryItem (crc32 ffadc913). */
final class TlStoryItemStoryItemSkipped extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_story_item_story_item_skipped';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'close_friends' => 'bool',
        'live' => 'bool',
        'tl_id' => 'int',
        'date' => 'int',
        'expire_date' => 'int',
    ];
}
