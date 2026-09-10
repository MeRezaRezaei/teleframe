<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for phoneCallDiscardReasonDisconnect of PhoneCallDiscardReason (crc32 e095c1a0). */
final class TlPhoneCallDiscardReasonPhoneCallDiscardReasonDisconnect extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_call_discard_reason_phone_call_disca_a0f9fca9390c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
