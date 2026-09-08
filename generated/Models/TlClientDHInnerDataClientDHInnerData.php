<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for client_DH_inner_data of Client_DH_Inner_Data (crc32 6643b654). */
final class TlClientDHInnerDataClientDHInnerData extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_client__d_h__inner__data_client__d_h_inner_data';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'nonce' => 'string',
        'server_nonce' => 'string',
        'retry_id' => 'int',
        'g_b' => 'string',
    ];
}
