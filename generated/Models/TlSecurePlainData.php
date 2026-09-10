<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValue;

/** Anchor model for TL type SecurePlainData (spec §4.1). */
final class TlSecurePlainData extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_plain_data_secure_plain_email';

    protected $guarded = [];

    public function plainData(): HasMany
    {
        return $this->hasMany(TlSecureValueSecureValue::class, 'plain_data');
    }
    public function plainDataInputSecureValue(): HasMany
    {
        return $this->hasMany(TlInputSecureValueInputSecureValue::class, 'plain_data');
    }
}
