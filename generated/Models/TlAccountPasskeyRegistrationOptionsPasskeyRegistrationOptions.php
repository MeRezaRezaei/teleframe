<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.passkeyRegistrationOptions of account.PasskeyRegistrationOptions (crc32 e16b5ce1). */
final class TlAccountPasskeyRegistrationOptionsPasskeyRegistrationOptions extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_passkey_registration_options_passk_47a076e67747';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'options' => 'string',
    ];
}
