<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for securePasswordKdfAlgoSHA512 of SecurePasswordKdfAlgo (crc32 86471d92). */
final class TlSecurePasswordKdfAlgoSecurePasswordKdfAlgoSHA512 extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_password_kdf_algo_secure_password_k_b4962aea68ba';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'salt' => 'string',
    ];
}
