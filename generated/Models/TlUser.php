<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthAuthorizationAuthorization;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpSupportSupport;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUrlAuthResultUrlAuthResultRequest;

/** Anchor model for TL type User (spec §4.1). */
final class TlUser extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_user_user';

    protected $guarded = [];

    public function bot(): HasMany
    {
        return $this->hasMany(TlUrlAuthResultUrlAuthResultRequest::class, 'bot');
    }
    public function user(): HasMany
    {
        return $this->hasMany(TlAuthAuthorizationAuthorization::class, 'tl_user');
    }
    public function userHelpSupport(): HasMany
    {
        return $this->hasMany(TlHelpSupportSupport::class, 'tl_user');
    }
}
