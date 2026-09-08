<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageTableRowPageTableRowCells;

/** Constructor model for pageTableRow of PageTableRow (crc32 e0c0c5e5). */
final class TlPageTableRowPageTableRow extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_table_row_page_table_row';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function cells(): HasMany
    {
        return $this->tlChild(TlPageTableRowPageTableRowCells::class);
    }
}
