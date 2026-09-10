<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for secureCredentialsEncrypted of SecureCredentialsEncrypted (crc32 33f0ea47). */
final class TlSecureCredentialsEncryptedSecureCredentialsEncrypted extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_credentials_encrypted_secure_creden_5d7271a97981';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'string',
        'hash' => 'string',
        'secret' => 'string',
    ];
}
