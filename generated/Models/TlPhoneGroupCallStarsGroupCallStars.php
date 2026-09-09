<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallStarsGroupCallStarsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallStarsGroupCallStarsTop_donors;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoneGroupCallStarsGroupCallStarsUsers;

/** Constructor model for phone.groupCallStars of phone.GroupCallStars (crc32 9d1dbd26). */
final class TlPhoneGroupCallStarsGroupCallStars extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_phone_group_call_stars_group_call_stars';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'total_stars' => 'int',
    ];

    public function topDonors(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallStarsGroupCallStarsTop_donors::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallStarsGroupCallStarsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPhoneGroupCallStarsGroupCallStarsUsers::class);
    }
}
