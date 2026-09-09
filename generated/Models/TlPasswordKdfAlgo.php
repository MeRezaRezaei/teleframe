<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordInputSettingsPasswordInputSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountPasswordPassword;

/** Anchor model for TL type PasswordKdfAlgo (spec §4.1). */
final class TlPasswordKdfAlgo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_password_kdf_algo';

    protected $guarded = [];

    public function currentAlgo(): HasMany
    {
        return $this->hasMany(TlAccountPasswordPassword::class, 'current_algo');
    }
    public function newAlgo(): HasMany
    {
        return $this->hasMany(TlAccountPasswordPassword::class, 'new_algo');
    }
    public function newAlgoAccountPasswordInputSettings(): HasMany
    {
        return $this->hasMany(TlAccountPasswordInputSettingsPasswordInputSettings::class, 'new_algo');
    }
}
