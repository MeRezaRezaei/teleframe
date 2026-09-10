<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValueFiles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValueTranslation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureData;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecurePlainData;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueType;

/** Constructor model for inputSecureValue of InputSecureValue (crc32 db21d0a7). */
final class TlInputSecureValueInputSecureValue extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_secure_value_input_secure_value';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function translation(): HasMany
    {
        return $this->tlChild(TlInputSecureValueInputSecureValueTranslation::class);
    }
    public function files(): HasMany
    {
        return $this->tlChild(TlInputSecureValueInputSecureValueFiles::class);
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
        return $this->belongsTo(TlInputSecureFile::class, 'front_side');
    }
    public function reverseSide(): BelongsTo
    {
        return $this->belongsTo(TlInputSecureFile::class, 'reverse_side');
    }
    public function selfie(): BelongsTo
    {
        return $this->belongsTo(TlInputSecureFile::class, 'selfie');
    }
    public function plainData(): BelongsTo
    {
        return $this->belongsTo(TlSecurePlainData::class, 'plain_data');
    }
}
