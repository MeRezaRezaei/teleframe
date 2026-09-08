<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockDetailsBlocks;

/** Constructor model for pageBlockDetails of PageBlock (crc32 76768bed). */
final class TlPageBlockPageBlockDetails extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_details';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'open' => 'bool',
        'title' => 'string',
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockDetailsBlocks::class);
    }
}
