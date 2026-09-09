<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordPassword;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureSecretSettingsSecureSecretSettings;

/** Anchor model for TL type SecurePasswordKdfAlgo (spec §4.1). */
final class TlSecurePasswordKdfAlgo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_password_kdf_algo';

    protected $guarded = [];

    public function newSecureAlgo(): HasMany
    {
        return $this->hasMany(TlAccountPasswordPassword::class, 'new_secure_algo');
    }
    public function secureAlgo(): HasMany
    {
        return $this->hasMany(TlSecureSecretSettingsSecureSecretSettings::class, 'secure_algo');
    }
}
