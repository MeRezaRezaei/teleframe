<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starGiftAttributeBackdrop of StarGiftAttribute (crc32 9f2504e4). */
final class TlStarGiftAttributeStarGiftAttributeBackdrop extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_star_gift_attribute_star_gift_attribute_backdrop';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'name' => 'string',
        'backdrop_id' => 'int',
        'center_color' => 'int',
        'edge_color' => 'int',
        'pattern_color' => 'int',
        'text_color' => 'int',
        'rarity' => 'string',
    ];
}
