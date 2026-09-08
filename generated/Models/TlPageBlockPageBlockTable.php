<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockTableRows;

/** Constructor model for pageBlockTable of PageBlock (crc32 bf4dea82). */
final class TlPageBlockPageBlockTable extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_table';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'bordered' => 'bool',
        'striped' => 'bool',
        'title' => 'string',
    ];

    public function rows(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockTableRows::class);
    }
}
