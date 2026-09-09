<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param document_id (table tl_emoji_list_emoji_list__document_id). */
final class TlEmojiListEmojiListDocument_id extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_emoji_list_emoji_list__document_id';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
