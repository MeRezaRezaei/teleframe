<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPasskeyCredentialInputPasskeyCredentialPublicKey;

/** Anchor model for TL type InputPasskeyResponse (spec §4.1). */
final class TlInputPasskeyResponse extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_passkey_response';

    protected $guarded = [];

    public function response(): HasMany
    {
        return $this->hasMany(TlInputPasskeyCredentialInputPasskeyCredentialPublicKey::class, 'response');
    }
}
