<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactBirthdayContactBirthday;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestBirthday;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type Birthday (spec §4.1). */
final class TlBirthday extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_birthday';

    protected $guarded = [];

    public function birthday(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSuggestBirthday::class, 'birthday');
    }
    public function birthdayContactBirthday(): HasMany
    {
        return $this->hasMany(TlContactBirthdayContactBirthday::class, 'birthday');
    }
    public function birthdayUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'birthday');
    }
}
