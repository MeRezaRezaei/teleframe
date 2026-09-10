<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for disallowedGiftsSettings of DisallowedGiftsSettings (crc32 71f276c4). */
final class TlDisallowedGiftsSettingsDisallowedGiftsSettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_disallowed_gifts_settings_disallowed_gifts_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'disallow_unlimited_stargifts' => 'bool',
        'disallow_limited_stargifts' => 'bool',
        'disallow_unique_stargifts' => 'bool',
        'disallow_premium_gifts' => 'bool',
        'disallow_stargifts_from_channels' => 'bool',
    ];
}
