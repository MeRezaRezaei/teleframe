<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValue;

/** Anchor model for TL type SecureFile (spec §4.1). */
final class TlSecureFile extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_file';

    protected $guarded = [];

    public function frontSide(): HasMany
    {
        return $this->hasMany(TlSecureValueSecureValue::class, 'front_side');
    }
    public function reverseSide(): HasMany
    {
        return $this->hasMany(TlSecureValueSecureValue::class, 'reverse_side');
    }
    public function selfie(): HasMany
    {
        return $this->hasMany(TlSecureValueSecureValue::class, 'selfie');
    }
}
