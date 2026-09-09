<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for p_q_inner_data_dc of P_Q_inner_data (crc32 a9f55f95). */
final class TlPQInnerDataPQInnerDataDc extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_p__q_inner_data_p_q_inner_data_dc';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pq' => 'string',
        'p' => 'string',
        'q' => 'string',
        'nonce' => 'string',
        'server_nonce' => 'string',
        'new_nonce' => 'string',
        'dc' => 'int',
    ];
}
