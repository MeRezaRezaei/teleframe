<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for paidReactionPrivacyAnonymous of PaidReactionPrivacy (crc32 1f0c1ad9). */
final class TlPaidReactionPrivacyPaidReactionPrivacyAnonymous extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_paid_reaction_privacy_paid_reaction_privacy_anonymous';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
