<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for emojiStatusCollectible of EmojiStatus (crc32 7184603b). */
final class TlEmojiStatusEmojiStatusCollectible extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_emoji_status_emoji_status_collectible';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'collectible_id' => 'int',
        'document_id' => 'int',
        'title' => 'string',
        'slug' => 'string',
        'pattern_document_id' => 'int',
        'center_color' => 'int',
        'edge_color' => 'int',
        'pattern_color' => 'int',
        'text_color' => 'int',
        'until' => 'int',
    ];
}
