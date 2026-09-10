<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for passwordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow of PasswordKdfAlgo (crc32 3a912d4a). */
final class TlPasswordKdfAlgoPasswordKdfAlgoSHA256SHA256PBKDF2HMACSHA512iter100000SHA256ModPow extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_password_kdf_algo_password_kdf_algo_s_h_a2_ac2e9e239dcc';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'salt1' => 'string',
        'salt2' => 'string',
        'g' => 'int',
        'p' => 'string',
    ];
}
