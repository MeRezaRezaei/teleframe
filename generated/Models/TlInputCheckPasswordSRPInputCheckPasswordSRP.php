<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputCheckPasswordSRP of InputCheckPasswordSRP (crc32 d27ff082). */
final class TlInputCheckPasswordSRPInputCheckPasswordSRP extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_check_password_s_r_p_input_check_password_s_r_p';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'srp_id' => 'int',
        'a' => 'string',
        'm1' => 'string',
    ];
}
