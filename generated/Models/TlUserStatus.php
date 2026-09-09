<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactStatusContactStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateUserStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

/** Anchor model for TL type UserStatus (spec §4.1). */
final class TlUserStatus extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_user_status';

    protected $guarded = [];

    public function status(): HasMany
    {
        return $this->hasMany(TlUserUser::class, 'status');
    }
    public function statusContactStatus(): HasMany
    {
        return $this->hasMany(TlContactStatusContactStatus::class, 'status');
    }
    public function statusUpdateUserStatus(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateUserStatus::class, 'status');
    }
}
