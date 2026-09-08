<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputReplyToStory of InputReplyTo (crc32 5881323a). */
final class TlInputReplyToInputReplyToStory extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_reply_to_input_reply_to_story';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'story_id' => 'int',
    ];
}
