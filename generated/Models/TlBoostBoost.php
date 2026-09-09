<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for boost of Boost (crc32 4b3e14d6). */
final class TlBoostBoost extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_boost_boost';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'gift' => 'bool',
        'giveaway' => 'bool',
        'unclaimed' => 'bool',
        'tl_id' => 'string',
        'user_id' => 'int',
        'giveaway_msg_id' => 'int',
        'date' => 'int',
        'expires' => 'int',
        'used_gift_slug' => 'string',
        'multiplier' => 'int',
        'stars' => 'int',
    ];
}
