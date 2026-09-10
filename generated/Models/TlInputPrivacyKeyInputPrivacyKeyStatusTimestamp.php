<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputPrivacyKeyStatusTimestamp of InputPrivacyKey (crc32 4f96cb18). */
final class TlInputPrivacyKeyInputPrivacyKeyStatusTimestamp extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_privacy_key_input_privacy_key_status_timestamp';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
