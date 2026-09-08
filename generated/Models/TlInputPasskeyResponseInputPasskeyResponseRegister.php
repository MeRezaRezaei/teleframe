<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPasskeyResponseRegister of InputPasskeyResponse (crc32 3e63935c). */
final class TlInputPasskeyResponseInputPasskeyResponseRegister extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_passkey_response_input_passkey_response_register';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'client_data' => 'string',
        'attestation_data' => 'string',
    ];
}
