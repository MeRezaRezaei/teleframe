<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type RecentMeUrl (spec §4.1). */
final class TlRecentMeUrl extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_recent_me_url_recent_me_url_chat';

    protected $guarded = [];
}
