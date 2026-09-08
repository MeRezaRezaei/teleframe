<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for server_DH_inner_data of Server_DH_inner_data (crc32 b5890dba). */
final class TlServerDHInnerDataServerDHInnerData extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_server__d_h_inner_data_server__d_h_inner_data';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'nonce' => 'string',
        'server_nonce' => 'string',
        'g' => 'int',
        'dh_prime' => 'string',
        'g_a' => 'string',
        'server_time' => 'int',
    ];
}
