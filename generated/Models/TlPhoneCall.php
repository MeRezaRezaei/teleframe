<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhonePhoneCallPhoneCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePhoneCall;

/** Anchor model for TL type PhoneCall (spec §4.1). */
final class TlPhoneCall extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_call_phone_call';

    protected $guarded = [];

    public function phoneCall(): HasMany
    {
        return $this->hasMany(TlUpdateUpdatePhoneCall::class, 'phone_call');
    }
    public function phoneCallPhonePhoneCall(): HasMany
    {
        return $this->hasMany(TlPhonePhoneCallPhoneCall::class, 'phone_call');
    }
}
