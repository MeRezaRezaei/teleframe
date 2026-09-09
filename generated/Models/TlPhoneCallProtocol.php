<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallAccepted;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallRequested;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCallPhoneCallWaiting;

/** Anchor model for TL type PhoneCallProtocol (spec §4.1). */
final class TlPhoneCallProtocol extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_call_protocol';

    protected $guarded = [];

    public function protocol(): HasMany
    {
        return $this->hasMany(TlPhoneCallPhoneCallWaiting::class, 'protocol');
    }
    public function protocolPhoneCall(): HasMany
    {
        return $this->hasMany(TlPhoneCallPhoneCall::class, 'protocol');
    }
    public function protocolPhoneCallAccepted(): HasMany
    {
        return $this->hasMany(TlPhoneCallPhoneCallAccepted::class, 'protocol');
    }
    public function protocolPhoneCallRequested(): HasMany
    {
        return $this->hasMany(TlPhoneCallPhoneCallRequested::class, 'protocol');
    }
}
