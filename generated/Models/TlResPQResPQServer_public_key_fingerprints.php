<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param server_public_key_fingerprints (table tl_res_p_q_res_p_q__server_public_key_fingerprints). */
final class TlResPQResPQServer_public_key_fingerprints extends TlAnchorModel
{
    protected $table = 'tl_res_p_q_res_p_q__server_public_key_fingerprints';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
