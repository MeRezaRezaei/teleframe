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

/** Constructor model for pageBlockAuthorDate of PageBlock (crc32 baafe5e0). */
final class TlPageBlockPageBlockAuthorDate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_author_date';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'published_date' => 'int',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'author');
    }
}
