<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValueTranslation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSecureValueInputSecureValueFiles;

/** Constructor model for inputSecureValue of InputSecureValue (crc32 db21d0a7). */
final class TlInputSecureValueInputSecureValue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_secure_value_input_secure_value';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_type' => 'string',
        'data' => 'string',
        'front_side' => 'string',
        'reverse_side' => 'string',
        'selfie' => 'string',
        'plain_data' => 'string',
    ];

    public function translation(): HasMany
    {
        return $this->tlChild(TlInputSecureValueInputSecureValueTranslation::class);
    }
    public function files(): HasMany
    {
        return $this->tlChild(TlInputSecureValueInputSecureValueFiles::class);
    }
}
