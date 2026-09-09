<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for passwordKdfAlgoUnknown of PasswordKdfAlgo (crc32 d45ab096). */
final class TlPasswordKdfAlgoPasswordKdfAlgoUnknown extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_password_kdf_algo_password_kdf_algo_unknown';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
