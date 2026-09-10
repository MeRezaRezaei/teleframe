<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for starGiftAttributeIdPattern of StarGiftAttributeId (crc32 4a162433). */
final class TlStarGiftAttributeIdStarGiftAttributeIdPattern extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_attribute_id_star_gift_attribute_id_pattern';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'document_id' => 'int',
    ];
}
