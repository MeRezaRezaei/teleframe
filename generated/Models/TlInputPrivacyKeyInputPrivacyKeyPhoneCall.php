<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPrivacyKeyPhoneCall of InputPrivacyKey (crc32 fabadc5f). */
final class TlInputPrivacyKeyInputPrivacyKeyPhoneCall extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_key_input_privacy_key_phone_call';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
