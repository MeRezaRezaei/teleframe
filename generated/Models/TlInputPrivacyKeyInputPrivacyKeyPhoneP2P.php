<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPrivacyKeyPhoneP2P of InputPrivacyKey (crc32 db9e70d2). */
final class TlInputPrivacyKeyInputPrivacyKeyPhoneP2P extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_key_input_privacy_key_phone_p2_p';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
