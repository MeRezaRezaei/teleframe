<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param blocked (table tl_contacts_blocked_blocked_slice__blocked). */
final class TlContactsBlockedBlockedSliceBlocked extends TlAnchorModel
{
    protected $table = 'tl_contacts_blocked_blocked_slice__blocked';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
