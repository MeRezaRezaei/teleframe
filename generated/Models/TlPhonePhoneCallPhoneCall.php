<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhonePhoneCallPhoneCallUsers;

/** Constructor model for phone.phoneCall of phone.PhoneCall (crc32 ec82e140). */
final class TlPhonePhoneCallPhoneCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_phone_call_phone_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPhonePhoneCallPhoneCallUsers::class);
    }

    public function phoneCall(): BelongsTo
    {
        return $this->belongsTo(TlPhoneCall::class, 'phone_call');
    }
}
