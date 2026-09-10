<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValue;

/** Anchor model for TL type InputSecureFile (spec §4.1). */
final class TlInputSecureFile extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_secure_file_input_secure_file';

    protected $guarded = [];

    public function frontSide(): HasMany
    {
        return $this->hasMany(TlInputSecureValueInputSecureValue::class, 'front_side');
    }
    public function reverseSide(): HasMany
    {
        return $this->hasMany(TlInputSecureValueInputSecureValue::class, 'reverse_side');
    }
    public function selfie(): HasMany
    {
        return $this->hasMany(TlInputSecureValueInputSecureValue::class, 'selfie');
    }
}
