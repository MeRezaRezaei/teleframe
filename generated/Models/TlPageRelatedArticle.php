<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type PageRelatedArticle (spec §4.1). */
final class TlPageRelatedArticle extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_page_related_article';

    protected $guarded = [];
}
