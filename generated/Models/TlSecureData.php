<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValue;

/** Anchor model for TL type SecureData (spec §4.1). */
final class TlSecureData extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_data_secure_data';

    protected $guarded = [];

    public function data(): HasMany
    {
        return $this->hasMany(TlSecureValueSecureValue::class, 'data');
    }
    public function dataInputSecureValue(): HasMany
    {
        return $this->hasMany(TlInputSecureValueInputSecureValue::class, 'data');
    }
}
