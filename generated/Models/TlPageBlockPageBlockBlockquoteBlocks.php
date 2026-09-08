<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockBlockquoteBlocksBlocks;

/** Constructor model for pageBlockBlockquoteBlocks of PageBlock (crc32 0e6e47c4). */
final class TlPageBlockPageBlockBlockquoteBlocks extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_blockquote_blocks';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'caption' => 'string',
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockBlockquoteBlocksBlocks::class);
    }
}
