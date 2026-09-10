<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureRequiredTypeSecureRequiredType;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueError;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorData;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFiles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFrontSide;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorReverseSide;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorSelfie;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorTranslationFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorTranslationFiles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueHashSecureValueHash;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValue;

/** Anchor model for TL type SecureValueType (spec §4.1). */
final class TlSecureValueType extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_value_type_secure_value_type_address';

    protected $guarded = [];

    public function type(): HasMany
    {
        return $this->hasMany(TlSecureValueSecureValue::class, 'tl_type');
    }
    public function typeInputSecureValue(): HasMany
    {
        return $this->hasMany(TlInputSecureValueInputSecureValue::class, 'tl_type');
    }
    public function typeSecureRequiredType(): HasMany
    {
        return $this->hasMany(TlSecureRequiredTypeSecureRequiredType::class, 'tl_type');
    }
    public function typeSecureValueError(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueError::class, 'tl_type');
    }
    public function typeSecureValueErrorData(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorData::class, 'tl_type');
    }
    public function typeSecureValueErrorFile(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorFile::class, 'tl_type');
    }
    public function typeSecureValueErrorFiles(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorFiles::class, 'tl_type');
    }
    public function typeSecureValueErrorFrontSide(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorFrontSide::class, 'tl_type');
    }
    public function typeSecureValueErrorReverseSide(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorReverseSide::class, 'tl_type');
    }
    public function typeSecureValueErrorSelfie(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorSelfie::class, 'tl_type');
    }
    public function typeSecureValueErrorTranslationFile(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorTranslationFile::class, 'tl_type');
    }
    public function typeSecureValueErrorTranslationFiles(): HasMany
    {
        return $this->hasMany(TlSecureValueErrorSecureValueErrorTranslationFiles::class, 'tl_type');
    }
    public function typeSecureValueHash(): HasMany
    {
        return $this->hasMany(TlSecureValueHashSecureValueHash::class, 'tl_type');
    }
}
