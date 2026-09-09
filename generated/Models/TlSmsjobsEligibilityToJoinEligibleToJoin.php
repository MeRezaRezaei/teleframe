<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for smsjobs.eligibleToJoin of smsjobs.EligibilityToJoin (crc32 dc8b44cf). */
final class TlSmsjobsEligibilityToJoinEligibleToJoin extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_smsjobs_eligibility_to_join_eligible_to_join';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'terms_url' => 'string',
        'monthly_sent_sms' => 'int',
    ];
}
