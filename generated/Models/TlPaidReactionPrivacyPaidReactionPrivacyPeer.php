<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for paidReactionPrivacyPeer of PaidReactionPrivacy (crc32 dc6cfcf0). */
final class TlPaidReactionPrivacyPaidReactionPrivacyPeer extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_paid_reaction_privacy_paid_reaction_privacy_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
