<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type payments.SuggestedStarRefBots (spec §4.1). */
final class TlPaymentsSuggestedStarRefBots extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_suggested_star_ref_bots_suggested_2b419606faf4';

    protected $guarded = [];
}
