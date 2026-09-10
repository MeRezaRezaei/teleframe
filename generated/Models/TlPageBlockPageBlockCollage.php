<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockCollageItems;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageCaption;

/** Constructor model for pageBlockCollage of PageBlock (crc32 65a0fa4d). */
final class TlPageBlockPageBlockCollage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_collage';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function items(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockCollageItems::class);
    }

    public function caption(): BelongsTo
    {
        return $this->belongsTo(TlPageCaption::class, 'caption');
    }
}
