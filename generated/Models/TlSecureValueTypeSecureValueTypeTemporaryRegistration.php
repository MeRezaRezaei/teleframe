<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for secureValueTypeTemporaryRegistration of SecureValueType (crc32 ea02ec33). */
final class TlSecureValueTypeSecureValueTypeTemporaryRegistration extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_secure_value_type_secure_value_type_tempor_020a5e2a279c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
