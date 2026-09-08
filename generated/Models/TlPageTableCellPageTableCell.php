<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for pageTableCell of PageTableCell (crc32 34566b6a). */
final class TlPageTableCellPageTableCell extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_table_cell_page_table_cell';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'header' => 'bool',
        'align_center' => 'bool',
        'align_right' => 'bool',
        'valign_middle' => 'bool',
        'valign_bottom' => 'bool',
        'text' => 'string',
        'colspan' => 'int',
        'rowspan' => 'int',
    ];
}
