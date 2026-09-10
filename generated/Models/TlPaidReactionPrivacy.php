<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePaidReactionPrivacy;

/** Anchor model for TL type PaidReactionPrivacy (spec §4.1). */
final class TlPaidReactionPrivacy extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_paid_reaction_privacy_paid_reaction_privacy_anonymous';

    protected $guarded = [];

    public function private(): HasMany
    {
        return $this->hasMany(TlUpdateUpdatePaidReactionPrivacy::class, 'private');
    }
}
