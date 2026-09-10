<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for privacyKeyStarGiftsAutoSave of PrivacyKey (crc32 2ca4fdf8). */
final class TlPrivacyKeyPrivacyKeyStarGiftsAutoSave extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_privacy_key_privacy_key_star_gifts_auto_save';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
