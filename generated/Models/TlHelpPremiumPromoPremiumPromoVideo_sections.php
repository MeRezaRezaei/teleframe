<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param video_sections (table tl_help_premium_promo_premium_promo__video_sections). */
final class TlHelpPremiumPromoPremiumPromoVideo_sections extends TlAnchorModel
{
    protected $table = 'tl_help_premium_promo_premium_promo__video_sections';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
