<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsGiveawayWinnersOption of StarsGiveawayWinnersOption (crc32 54236209). */
final class TlStarsGiveawayWinnersOptionStarsGiveawayWinnersOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_giveaway_winners_option_stars_giveaw_0f7ecce3eb88';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_default' => 'bool',
        'users' => 'int',
        'per_user_stars' => 'int',
    ];
}
