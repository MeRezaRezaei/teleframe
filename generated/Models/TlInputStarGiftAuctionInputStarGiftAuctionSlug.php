<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputStarGiftAuctionSlug of InputStarGiftAuction (crc32 7ab58308). */
final class TlInputStarGiftAuctionInputStarGiftAuctionSlug extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_star_gift_auction_input_star_gift_auction_slug';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'slug' => 'string',
    ];
}
