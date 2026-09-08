<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param rows (table tl_reply_markup_reply_inline_markup__rows). */
final class TlReplyMarkupReplyInlineMarkupRows extends TlAnchorModel
{
    protected $table = 'tl_reply_markup_reply_inline_markup__rows';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
