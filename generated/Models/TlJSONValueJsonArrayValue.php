<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param value (table tl_j_s_o_n_value_json_array__value). */
final class TlJSONValueJsonArrayValue extends TlAnchorModel
{
    protected $table = 'tl_j_s_o_n_value_json_array__value';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
