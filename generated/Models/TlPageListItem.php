<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type PageListItem (spec §4.1). */
final class TlPageListItem extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_page_list_item_page_list_item_blocks';

    protected $guarded = [];
}
