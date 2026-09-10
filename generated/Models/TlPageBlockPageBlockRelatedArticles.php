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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockRelatedArticlesArticles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichText;

/** Constructor model for pageBlockRelatedArticles of PageBlock (crc32 16115a96). */
final class TlPageBlockPageBlockRelatedArticles extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_related_articles';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function articles(): HasMany
    {
        return $this->tlChild(TlPageBlockPageBlockRelatedArticlesArticles::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'title');
    }
}
