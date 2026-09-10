<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for starGiftAttributeIdBackdrop of StarGiftAttributeId (crc32 1f01c757). */
final class TlStarGiftAttributeIdStarGiftAttributeIdBackdrop extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_attribute_id_star_gift_attribute_id_backdrop';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'backdrop_id' => 'int',
    ];
}
