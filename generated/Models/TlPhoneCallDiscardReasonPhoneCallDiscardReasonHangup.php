<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for phoneCallDiscardReasonHangup of PhoneCallDiscardReason (crc32 57adc690). */
final class TlPhoneCallDiscardReasonPhoneCallDiscardReasonHangup extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_phone_call_discard_reason_phone_call_disca_d9fe56a20c35';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
