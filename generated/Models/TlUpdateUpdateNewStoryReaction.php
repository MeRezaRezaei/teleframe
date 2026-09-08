<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for updateNewStoryReaction of Update (crc32 1824e40b). */
final class TlUpdateUpdateNewStoryReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_new_story_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'story_id' => 'int',
        'peer' => 'string',
        'reaction' => 'string',
    ];
}
