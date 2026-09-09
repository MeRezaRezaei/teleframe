<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichText;

/** Constructor model for pageListOrderedItemText of PageListOrderedItem (crc32 15031189). */
final class TlPageListOrderedItemPageListOrderedItemText extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_list_ordered_item_page_list_ordered_item_text';

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

    public function text(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'text');
    }
}
