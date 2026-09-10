<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type account.WallPapers (spec §4.1). */
final class TlAccountWallPapers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_wall_papers_wall_papers';

    protected $guarded = [];
}
