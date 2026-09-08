<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockListItems;

/** Constructor model for pageBlockList of PageBlock (crc32 e4e88011). */
final class TlPageBlockPageBlockList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function items(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockListItems::class);
    }
}
