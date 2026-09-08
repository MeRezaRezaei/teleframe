<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for fragment.collectibleInfo of fragment.CollectibleInfo (crc32 6ebdff91). */
final class TlFragmentCollectibleInfoCollectibleInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_fragment_collectible_info_collectible_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'purchase_date' => 'int',
        'currency' => 'string',
        'amount' => 'int',
        'crypto_currency' => 'string',
        'crypto_amount' => 'int',
        'url' => 'string',
    ];
}
