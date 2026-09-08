<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param blocks (table tl_page_block_page_block_embed_post__blocks). */
final class TlPageBlockPageBlockEmbedPostBlocks extends TlAnchorModel
{
    protected $table = 'tl_page_block_page_block_embed_post__blocks';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
