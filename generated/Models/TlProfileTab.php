<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type ProfileTab (spec §4.1). */
final class TlProfileTab extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_profile_tab';

    protected $guarded = [];

    public function mainTab(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'main_tab');
    }
    public function mainTabUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'main_tab');
    }
}
