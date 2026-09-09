<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for stories.canSendStoryCount of stories.CanSendStoryCount (crc32 c387c04e). */
final class TlStoriesCanSendStoryCountCanSendStoryCount extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stories_can_send_story_count_can_send_story_count';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count_remains' => 'int',
    ];
}
