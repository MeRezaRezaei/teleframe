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

/** Constructor model for auth.passkeyLoginOptions of auth.PasskeyLoginOptions (crc32 e2037789). */
final class TlAuthPasskeyLoginOptionsPasskeyLoginOptions extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auth_passkey_login_options_passkey_login_options';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function options(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'options');
    }
}
