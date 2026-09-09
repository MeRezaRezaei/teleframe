<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for rsa_public_key of RSAPublicKey (crc32 7a19cb76). */
final class TlRSAPublicKeyRsaPublicKey extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_r_s_a_public_key_rsa_public_key';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'n' => 'string',
        'e' => 'string',
    ];
}
