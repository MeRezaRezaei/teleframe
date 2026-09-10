<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type smsjobs.EligibilityToJoin (spec §4.1). */
final class TlSmsjobsEligibilityToJoin extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_smsjobs_eligibility_to_join_eligible_to_join';

    protected $guarded = [];
}
