<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordInputSettingsPasswordInputSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordSettingsPasswordSettings;

/** Anchor model for TL type SecureSecretSettings (spec §4.1). */
final class TlSecureSecretSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_secret_settings';

    protected $guarded = [];

    public function newSecureSettings(): HasMany
    {
        return $this->hasMany(TlAccountPasswordInputSettingsPasswordInputSettings::class, 'new_secure_settings');
    }
    public function secureSettings(): HasMany
    {
        return $this->hasMany(TlAccountPasswordSettingsPasswordSettings::class, 'secure_settings');
    }
}
