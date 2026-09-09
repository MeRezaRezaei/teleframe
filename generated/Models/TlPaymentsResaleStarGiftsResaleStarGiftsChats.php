<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_payments_resale_star_gifts_resale_star_gifts__chats). */
final class TlPaymentsResaleStarGiftsResaleStarGiftsChats extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_resale_star_gifts_resale_star_gifts__chats';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
