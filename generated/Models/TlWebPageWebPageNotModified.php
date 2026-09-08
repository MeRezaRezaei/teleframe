<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for webPageNotModified of WebPage (crc32 7311ca11). */
final class TlWebPageWebPageNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_page_web_page_not_modified';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'cached_page_views' => 'int',
    ];
}
