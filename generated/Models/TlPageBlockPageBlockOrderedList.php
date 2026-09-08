<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockOrderedListItems;

/** Constructor model for pageBlockOrderedList of PageBlock (crc32 1fd6f6c1). */
final class TlPageBlockPageBlockOrderedList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_ordered_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'reversed' => 'bool',
        'start' => 'int',
        'tl_type' => 'string',
    ];

    public function items(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockOrderedListItems::class);
    }
}
