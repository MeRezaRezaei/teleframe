<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockSlideshowItems;

/** Constructor model for pageBlockSlideshow of PageBlock (crc32 031f9590). */
final class TlPageBlockPageBlockSlideshow extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_block_page_block_slideshow';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'caption' => 'string',
    ];

    public function items(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockSlideshowItems::class);
    }
}
