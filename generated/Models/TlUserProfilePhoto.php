<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

/** Anchor model for TL type UserProfilePhoto (spec §4.1). */
final class TlUserProfilePhoto extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_user_profile_photo_user_profile_photo';

    protected $guarded = [];

    public function photo(): HasMany
    {
        return $this->hasMany(TlUserUser::class, 'photo');
    }
}
