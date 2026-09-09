<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for paidReactionPrivacyDefault of PaidReactionPrivacy (crc32 206ad49e). */
final class TlPaidReactionPrivacyPaidReactionPrivacyDefault extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_paid_reaction_privacy_paid_reaction_privacy_default';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
