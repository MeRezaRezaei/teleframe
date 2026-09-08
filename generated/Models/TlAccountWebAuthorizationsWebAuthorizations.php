<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountWebAuthorizationsWebAuthorizationsAuthorizations;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountWebAuthorizationsWebAuthorizationsUsers;

/** Constructor model for account.webAuthorizations of account.WebAuthorizations (crc32 ed56c9fc). */
final class TlAccountWebAuthorizationsWebAuthorizations extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_account_web_authorizations_web_authorizations';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function authorizations(): HasMany
    {
        return $this->tlChild(TlAccountWebAuthorizationsWebAuthorizationsAuthorizations::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAccountWebAuthorizationsWebAuthorizationsUsers::class);
    }
}
