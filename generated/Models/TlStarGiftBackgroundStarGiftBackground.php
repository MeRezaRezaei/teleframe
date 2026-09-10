<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for starGiftBackground of StarGiftBackground (crc32 aff56398). */
final class TlStarGiftBackgroundStarGiftBackground extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_background_star_gift_background';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'center_color' => 'int',
        'edge_color' => 'int',
        'text_color' => 'int',
    ];
}
