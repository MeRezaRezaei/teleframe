<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for pageRelatedArticle of PageRelatedArticle (crc32 b390dc08). */
final class TlPageRelatedArticlePageRelatedArticle extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_page_related_article_page_related_article';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'url' => 'string',
        'webpage_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'photo_id' => 'int',
        'author' => 'string',
        'published_date' => 'int',
    ];
}
