<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for phoneCallDiscardReasonMissed of PhoneCallDiscardReason (crc32 85e42301). */
final class TlPhoneCallDiscardReasonPhoneCallDiscardReasonMissed extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_call_discard_reason_phone_call_disca_73069d1fca2f';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
