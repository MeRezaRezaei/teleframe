<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param countries_iso2 (table tl_message_media_message_media_giveaway__countries_iso2). */
final class TlMessageMediaMessageMediaGiveawayCountries_iso2 extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_giveaway__countries_iso2';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
