<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for phoneCallDiscardReasonMigrateConferenceCall of PhoneCallDiscardReason (crc32 9fbbf1f7). */
final class TlPhoneCallDiscardReasonPhoneCallDiscardReasonMigrateConferenceCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_phone_call_discard_reason_phone_call_disca_622a32ffc276';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'slug' => 'string',
    ];
}
