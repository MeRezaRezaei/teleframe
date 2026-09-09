<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for starGiftAttributeRarityLegendary of StarGiftAttributeRarity (crc32 cef7e7a8). */
final class TlStarGiftAttributeRarityStarGiftAttributeRarityLegendary extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_attribute_rarity_star_gift_attri_5570ca926404';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
