<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageListOrderedItemPageListOrderedItemBlocksBlocks;

/** Constructor model for pageListOrderedItemBlocks of PageListOrderedItem (crc32 8ff2d5f0). */
final class TlPageListOrderedItemPageListOrderedItemBlocks extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_list_ordered_item_page_list_ordered_item_blocks';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'checkbox' => 'bool',
        'checked' => 'bool',
        'num' => 'string',
        'tl_value' => 'int',
        'tl_type' => 'string',
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlPageListOrderedItemPageListOrderedItemBlocksBlocks::class);
    }
}
