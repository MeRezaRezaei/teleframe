<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for server_DH_params_ok of Server_DH_Params (crc32 d0e8075c). */
final class TlServerDHParamsServerDHParamsOk extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_server__d_h__params_server__d_h_params_ok';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'nonce' => 'string',
        'server_nonce' => 'string',
        'encrypted_answer' => 'string',
    ];
}
