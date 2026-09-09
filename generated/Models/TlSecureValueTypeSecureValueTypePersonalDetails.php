<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for secureValueTypePersonalDetails of SecureValueType (crc32 9d2a81e3). */
final class TlSecureValueTypeSecureValueTypePersonalDetails extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_value_type_secure_value_type_personal_details';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
