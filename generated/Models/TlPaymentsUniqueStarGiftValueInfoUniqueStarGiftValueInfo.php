<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for payments.uniqueStarGiftValueInfo of payments.UniqueStarGiftValueInfo (crc32 512fe446). */
final class TlPaymentsUniqueStarGiftValueInfoUniqueStarGiftValueInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_unique_star_gift_value_info_uniqu_435563956ba2';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'last_sale_on_fragment' => 'bool',
        'value_is_average' => 'bool',
        'currency' => 'string',
        'tl_value' => 'int',
        'initial_sale_date' => 'int',
        'initial_sale_stars' => 'int',
        'initial_sale_price' => 'int',
        'last_sale_date' => 'int',
        'last_sale_price' => 'int',
        'floor_price' => 'int',
        'average_price' => 'int',
        'listed_count' => 'int',
        'fragment_listed_count' => 'int',
        'fragment_listed_url' => 'string',
    ];
}
