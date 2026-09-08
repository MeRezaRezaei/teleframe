<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param blocks (table tl_update_update_group_call_chain_blocks__blocks). */
final class TlUpdateUpdateGroupCallChainBlocksBlocks extends TlAnchorModel
{
    protected $table = 'tl_update_update_group_call_chain_blocks__blocks';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
