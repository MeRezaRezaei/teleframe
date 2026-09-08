<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for dh_gen_fail of Set_client_DH_params_answer (crc32 a69dae02). */
final class TlSetClientDHParamsAnswerDhGenFail extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_set_client__d_h_params_answer_dh_gen_fail';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'nonce' => 'string',
        'server_nonce' => 'string',
        'new_nonce_hash3' => 'string',
    ];
}
