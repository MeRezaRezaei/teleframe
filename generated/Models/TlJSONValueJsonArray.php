<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONValueJsonArrayValue;

/** Constructor model for jsonArray of JSONValue (crc32 f7444763). */
final class TlJSONValueJsonArray extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_j_s_o_n_value_json_array';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function value(): HasMany
    {
        return $this->tlChild(TlJSONValueJsonArrayValue::class);
    }
}
