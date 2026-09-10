<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSecureValuesSentMe;

/** Anchor model for TL type SecureCredentialsEncrypted (spec §4.1). */
final class TlSecureCredentialsEncrypted extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_secure_credentials_encrypted_secure_creden_5d7271a97981';

    protected $guarded = [];

    public function credentials(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSecureValuesSentMe::class, 'credentials');
    }
}
