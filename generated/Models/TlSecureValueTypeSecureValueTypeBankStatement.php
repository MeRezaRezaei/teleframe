<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for secureValueTypeBankStatement of SecureValueType (crc32 89137c0d). */
final class TlSecureValueTypeSecureValueTypeBankStatement extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_value_type_secure_value_type_bank_statement';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
