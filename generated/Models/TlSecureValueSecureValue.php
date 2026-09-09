<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureData;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecurePlainData;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValueFiles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueSecureValueTranslation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueType;

/** Constructor model for secureValue of SecureValue (crc32 187fa0ca). */
final class TlSecureValueSecureValue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_value_secure_value';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'hash' => 'string',
    ];

    public function translation(): HasMany
    {
        return $this->tlChild(TlSecureValueSecureValueTranslation::class);
    }
    public function files(): HasMany
    {
        return $this->tlChild(TlSecureValueSecureValueFiles::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TlSecureValueType::class, 'tl_type');
    }
    public function data(): BelongsTo
    {
        return $this->belongsTo(TlSecureData::class, 'data');
    }
    public function frontSide(): BelongsTo
    {
        return $this->belongsTo(TlSecureFile::class, 'front_side');
    }
    public function reverseSide(): BelongsTo
    {
        return $this->belongsTo(TlSecureFile::class, 'reverse_side');
    }
    public function selfie(): BelongsTo
    {
        return $this->belongsTo(TlSecureFile::class, 'selfie');
    }
    public function plainData(): BelongsTo
    {
        return $this->belongsTo(TlSecurePlainData::class, 'plain_data');
    }
}
