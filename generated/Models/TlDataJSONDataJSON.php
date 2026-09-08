<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for dataJSON of DataJSON (crc32 7d748d04). */
final class TlDataJSONDataJSON extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_data_j_s_o_n_data_j_s_o_n';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'string',
    ];
}
