<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for privacyKeyPhoneP2P of PrivacyKey (crc32 39491cc8). */
final class TlPrivacyKeyPrivacyKeyPhoneP2P extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_privacy_key_privacy_key_phone_p2_p';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
