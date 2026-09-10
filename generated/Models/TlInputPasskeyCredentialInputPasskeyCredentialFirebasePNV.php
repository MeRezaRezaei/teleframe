<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputPasskeyCredentialFirebasePNV of InputPasskeyCredential (crc32 5b1ccb28). */
final class TlInputPasskeyCredentialInputPasskeyCredentialFirebasePNV extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_passkey_credential_input_passkey_cre_f2c081028727';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pnv_token' => 'string',
    ];
}
