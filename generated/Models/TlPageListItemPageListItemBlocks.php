<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageListItemPageListItemBlocksBlocks;

/** Constructor model for pageListItemBlocks of PageListItem (crc32 63ca67aa). */
final class TlPageListItemPageListItemBlocks extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_list_item_page_list_item_blocks';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'checkbox' => 'bool',
        'checked' => 'bool',
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlPageListItemPageListItemBlocksBlocks::class);
    }
}
