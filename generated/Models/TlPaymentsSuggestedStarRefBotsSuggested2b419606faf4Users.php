<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_payments_suggested_star_ref_bots_suggested_a10aaedcb3f4). */
final class TlPaymentsSuggestedStarRefBotsSuggested2b419606faf4Users extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_suggested_star_ref_bots_suggested_a10aaedcb3f4';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
