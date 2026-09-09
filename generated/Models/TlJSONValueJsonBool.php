<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for jsonBool of JSONValue (crc32 c7345e6a). */
final class TlJSONValueJsonBool extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_j_s_o_n_value_json_bool';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function value(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'tl_value');
    }
}
