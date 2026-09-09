<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueType;

/** Constructor model for secureValueErrorTranslationFile of SecureValueError (crc32 a1144770). */
final class TlSecureValueErrorSecureValueErrorTranslationFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_value_error_secure_value_error_translation_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'file_hash' => 'string',
        'text' => 'string',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TlSecureValueType::class, 'tl_type');
    }
}
