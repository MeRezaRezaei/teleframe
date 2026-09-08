<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param emoticons (table tl_emoji_keyword_emoji_keyword_deleted__emoticons). */
final class TlEmojiKeywordEmojiKeywordDeletedEmoticons extends TlAnchorModel
{
    protected $table = 'tl_emoji_keyword_emoji_keyword_deleted__emoticons';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
