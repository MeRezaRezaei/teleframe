<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for jsonBool of JSONValue (crc32 c7345e6a). */
final class TlJSONValueJsonBool extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_j_s_o_n_value_json_bool';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_value' => 'string',
    ];
}
