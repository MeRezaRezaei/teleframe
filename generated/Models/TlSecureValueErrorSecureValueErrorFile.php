<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for secureValueErrorFile of SecureValueError (crc32 7a700873). */
final class TlSecureValueErrorSecureValueErrorFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_value_error_secure_value_error_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_type' => 'string',
        'file_hash' => 'string',
        'text' => 'string',
    ];
}
