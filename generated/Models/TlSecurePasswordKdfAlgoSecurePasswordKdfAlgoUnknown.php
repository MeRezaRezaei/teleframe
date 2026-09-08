<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for securePasswordKdfAlgoUnknown of SecurePasswordKdfAlgo (crc32 004a8537). */
final class TlSecurePasswordKdfAlgoSecurePasswordKdfAlgoUnknown extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_password_kdf_algo_secure_password_k_9caca554aa37';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
