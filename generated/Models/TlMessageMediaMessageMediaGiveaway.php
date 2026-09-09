<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveawayChannels;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveawayCountries_iso2;

/** Constructor model for messageMediaGiveaway of MessageMedia (crc32 aa073beb). */
final class TlMessageMediaMessageMediaGiveaway extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_giveaway';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'only_new_subscribers' => 'bool',
        'winners_are_visible' => 'bool',
        'prize_description' => 'string',
        'quantity' => 'int',
        'months' => 'int',
        'stars' => 'int',
        'until_date' => 'int',
    ];

    public function channels(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaGiveawayChannels::class);
    }
    public function countriesIso2(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaGiveawayCountries_iso2::class);
    }
}
