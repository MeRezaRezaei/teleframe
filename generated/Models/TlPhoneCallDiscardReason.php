<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPhoneCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallDiscarded;

/** Anchor model for TL type PhoneCallDiscardReason (spec §4.1). */
final class TlPhoneCallDiscardReason extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_call_discard_reason';

    protected $guarded = [];

    public function reason(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionPhoneCall::class, 'reason');
    }
    public function reasonPhoneCallDiscarded(): HasMany
    {
        return $this->hasMany(TlPhoneCallPhoneCallDiscarded::class, 'reason');
    }
}
