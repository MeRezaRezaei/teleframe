<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for account.passwordSettings of account.PasswordSettings (crc32 9a5c33e5). */
final class TlAccountPasswordSettingsPasswordSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_password_settings_password_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'email' => 'string',
        'secure_settings' => 'string',
    ];
}
