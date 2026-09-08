<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPasskeyCredentialPublicKey of InputPasskeyCredential (crc32 3c27b78f). */
final class TlInputPasskeyCredentialInputPasskeyCredentialPublicKey extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'string',
        'raw_id' => 'string',
        'response' => 'string',
    ];
}
