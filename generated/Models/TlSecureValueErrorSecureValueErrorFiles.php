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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueErrorSecureValueErrorFilesFile_hash;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureValueType;

/** Constructor model for secureValueErrorFiles of SecureValueError (crc32 666220e9). */
final class TlSecureValueErrorSecureValueErrorFiles extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_value_error_secure_value_error_files';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'text' => 'string',
    ];

    public function fileHash(): HasMany
    {
        return $this->tlChild(TlSecureValueErrorSecureValueErrorFilesFile_hash::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TlSecureValueType::class, 'tl_type');
    }
}
