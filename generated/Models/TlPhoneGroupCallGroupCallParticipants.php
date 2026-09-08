<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param participants (table tl_phone_group_call_group_call__participants). */
final class TlPhoneGroupCallGroupCallParticipants extends TlAnchorModel
{
    protected $table = 'tl_phone_group_call_group_call__participants';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
