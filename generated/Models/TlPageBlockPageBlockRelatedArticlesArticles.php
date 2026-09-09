<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param articles (table tl_page_block_page_block_related_articles__articles). */
final class TlPageBlockPageBlockRelatedArticlesArticles extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_page_block_page_block_related_articles__articles';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
