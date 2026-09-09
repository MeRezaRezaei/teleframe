<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONValue;

/** Constructor model for jsonObjectValue of JSONObjectValue (crc32 c0de1bd9). */
final class TlJSONObjectValueJsonObjectValue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_j_s_o_n_object_value_json_object_value';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_key' => 'string',
    ];

    public function value(): BelongsTo
    {
        return $this->belongsTo(TlJSONValue::class, 'tl_value');
    }
}
