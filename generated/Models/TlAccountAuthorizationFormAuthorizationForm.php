<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAuthorizationFormAuthorizationFormErrors;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAuthorizationFormAuthorizationFormRequired_types;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAuthorizationFormAuthorizationFormUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAuthorizationFormAuthorizationFormValues;

/** Constructor model for account.authorizationForm of account.AuthorizationForm (crc32 ad2e1cd8). */
final class TlAccountAuthorizationFormAuthorizationForm extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_authorization_form_authorization_form';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'privacy_policy_url' => 'string',
    ];

    public function requiredTypes(): HasMany
    {
        return $this->tlChild(TlAccountAuthorizationFormAuthorizationFormRequired_types::class);
    }
    public function values(): HasMany
    {
        return $this->tlChild(TlAccountAuthorizationFormAuthorizationFormValues::class);
    }
    public function errors(): HasMany
    {
        return $this->tlChild(TlAccountAuthorizationFormAuthorizationFormErrors::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAccountAuthorizationFormAuthorizationFormUsers::class);
    }
}
