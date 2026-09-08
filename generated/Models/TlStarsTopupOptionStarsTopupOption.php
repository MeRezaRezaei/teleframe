<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsTopupOption of StarsTopupOption (crc32 0bd915c0). */
final class TlStarsTopupOptionStarsTopupOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_topup_option_stars_topup_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'extended' => 'bool',
        'stars' => 'int',
        'store_product' => 'string',
        'currency' => 'string',
        'amount' => 'int',
    ];
}
