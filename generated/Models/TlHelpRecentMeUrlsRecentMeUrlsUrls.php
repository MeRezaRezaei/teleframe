<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param urls (table tl_help_recent_me_urls_recent_me_urls__urls). */
final class TlHelpRecentMeUrlsRecentMeUrlsUrls extends TlAnchorModel
{
    protected $table = 'tl_help_recent_me_urls_recent_me_urls__urls';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
