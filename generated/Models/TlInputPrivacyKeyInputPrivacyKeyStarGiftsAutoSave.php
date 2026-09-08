<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPrivacyKeyStarGiftsAutoSave of InputPrivacyKey (crc32 e1732341). */
final class TlInputPrivacyKeyInputPrivacyKeyStarGiftsAutoSave extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_privacy_key_input_privacy_key_star_g_6e9efbe835c3';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
