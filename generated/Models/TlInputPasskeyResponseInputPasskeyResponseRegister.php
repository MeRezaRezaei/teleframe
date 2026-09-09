<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;

/** Constructor model for inputPasskeyResponseRegister of InputPasskeyResponse (crc32 3e63935c). */
final class TlInputPasskeyResponseInputPasskeyResponseRegister extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_passkey_response_input_passkey_response_register';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'attestation_data' => 'string',
    ];

    public function clientData(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'client_data');
    }
}
