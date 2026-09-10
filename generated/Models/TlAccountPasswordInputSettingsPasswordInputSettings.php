<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPasswordKdfAlgo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureSecretSettings;

/** Constructor model for account.passwordInputSettings of account.PasswordInputSettings (crc32 c23727c9). */
final class TlAccountPasswordInputSettingsPasswordInputSettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_password_input_settings_password_input_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'new_password_hash' => 'string',
        'hint' => 'string',
        'email' => 'string',
    ];

    public function newAlgo(): BelongsTo
    {
        return $this->belongsTo(TlPasswordKdfAlgo::class, 'new_algo');
    }
    public function newSecureSettings(): BelongsTo
    {
        return $this->belongsTo(TlSecureSecretSettings::class, 'new_secure_settings');
    }
}
