<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for paidReactionPrivacyPeer of PaidReactionPrivacy (crc32 dc6cfcf0). */
final class TlPaidReactionPrivacyPaidReactionPrivacyPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_paid_reaction_privacy_paid_reaction_privacy_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
    ];
}
