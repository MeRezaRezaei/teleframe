<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storyViewPublicForward of StoryView (crc32 9083670b). */
final class TlStoryViewStoryViewPublicForward extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_story_view_story_view_public_forward';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'blocked' => 'bool',
        'blocked_my_stories_from' => 'bool',
        'message' => 'string',
    ];
}
