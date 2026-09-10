<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockBlockquoteBlocksBlocks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichText;

/** Constructor model for pageBlockBlockquoteBlocks of PageBlock (crc32 0e6e47c4). */
final class TlPageBlockPageBlockBlockquoteBlocks extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_blockquote_blocks';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockBlockquoteBlocksBlocks::class);
    }

    public function caption(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'caption');
    }
}
