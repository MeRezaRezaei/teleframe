<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthLoginTokenLoginTokenSuccess;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeSentCodeSuccess;

/** Anchor model for TL type auth.Authorization (spec §4.1). */
final class TlAuthAuthorization extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_auth_authorization';

    protected $guarded = [];

    public function authorization(): HasMany
    {
        return $this->hasMany(TlAuthSentCodeSentCodeSuccess::class, 'tl_authorization');
    }
    public function authorizationAuthLoginTokenSuccess(): HasMany
    {
        return $this->hasMany(TlAuthLoginTokenLoginTokenSuccess::class, 'tl_authorization');
    }
}
