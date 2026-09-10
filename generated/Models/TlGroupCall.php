<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCall;

/** Anchor model for TL type GroupCall (spec §4.1). */
final class TlGroupCall extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_group_call_group_call';

    protected $guarded = [];

    public function call(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateGroupCall::class, 'call');
    }
    public function callPhoneGroupCall(): HasMany
    {
        return $this->hasMany(TlPhoneGroupCallGroupCall::class, 'call');
    }
}
