<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountEmailVerifiedEmailVerifiedLogin;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateSentPhoneCode;

/** Anchor model for TL type auth.SentCode (spec §4.1). */
final class TlAuthSentCode extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_auth_sent_code';

    protected $guarded = [];

    public function sentCode(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateSentPhoneCode::class, 'sent_code');
    }
    public function sentCodeAccountEmailVerifiedLogin(): HasMany
    {
        return $this->hasMany(TlAccountEmailVerifiedEmailVerifiedLogin::class, 'sent_code');
    }
}
