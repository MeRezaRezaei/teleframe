<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type SponsoredMessage (spec §4.1). */
final class TlSponsoredMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_sponsored_message_sponsored_message';

    protected $guarded = [];
}
