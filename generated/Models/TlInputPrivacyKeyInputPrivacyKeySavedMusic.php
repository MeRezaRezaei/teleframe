<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputPrivacyKeySavedMusic of InputPrivacyKey (crc32 4dbe9226). */
final class TlInputPrivacyKeyInputPrivacyKeySavedMusic extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_privacy_key_input_privacy_key_saved_music';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
