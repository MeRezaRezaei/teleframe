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

/** Constructor model for pageBlockPullquote of PageBlock (crc32 4f4456d3). */
final class TlPageBlockPageBlockPullquote extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_pullquote';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function text(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'text');
    }
    public function caption(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'caption');
    }
}
