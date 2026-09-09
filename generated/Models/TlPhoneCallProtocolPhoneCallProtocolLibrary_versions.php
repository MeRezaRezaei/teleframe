<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param library_versions (table tl_phone_call_protocol_phone_call_protocol__l_d2c022a39003). */
final class TlPhoneCallProtocolPhoneCallProtocolLibrary_versions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_phone_call_protocol_phone_call_protocol__l_d2c022a39003';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
