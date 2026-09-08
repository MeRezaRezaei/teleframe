<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for myBoost of MyBoost (crc32 c448415c). */
final class TlMyBoostMyBoost extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_my_boost_my_boost';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'slot' => 'int',
        'peer' => 'string',
        'date' => 'int',
        'expires' => 'int',
        'cooldown_until_date' => 'int',
    ];
}
