<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.password of account.Password (crc32 957b50fb). */
final class TlAccountPasswordPassword extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_password_password';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_recovery' => 'bool',
        'has_secure_values' => 'bool',
        'has_password' => 'bool',
        'current_algo' => 'string',
        'srp__b' => 'string',
        'srp_id' => 'int',
        'hint' => 'string',
        'email_unconfirmed_pattern' => 'string',
        'new_algo' => 'string',
        'new_secure_algo' => 'string',
        'secure_random' => 'string',
        'pending_reset_date' => 'int',
        'login_email_pattern' => 'string',
    ];
}
