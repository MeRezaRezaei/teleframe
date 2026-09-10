<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type DestroySessionRes (spec §4.1). */
final class TlDestroySessionRes extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_destroy_session_res_destroy_session_none';

    protected $guarded = [];
}
