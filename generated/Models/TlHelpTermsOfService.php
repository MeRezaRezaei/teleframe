<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthAuthorizationAuthorizationSignUpRequired;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpTermsOfServiceUpdateTermsOfServiceUpdate;

/** Anchor model for TL type help.TermsOfService (spec §4.1). */
final class TlHelpTermsOfService extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_terms_of_service';

    protected $guarded = [];

    public function termsOfService(): HasMany
    {
        return $this->hasMany(TlAuthAuthorizationAuthorizationSignUpRequired::class, 'terms_of_service');
    }
    public function termsOfServiceHelpTermsOfServiceUpdate(): HasMany
    {
        return $this->hasMany(TlHelpTermsOfServiceUpdateTermsOfServiceUpdate::class, 'terms_of_service');
    }
}
