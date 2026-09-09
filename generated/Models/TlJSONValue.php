<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpAppConfigAppConfig;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputAppEventInputAppEvent;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONObjectValueJsonObjectValue;

/** Anchor model for TL type JSONValue (spec §4.1). */
final class TlJSONValue extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_j_s_o_n_value';

    protected $guarded = [];

    public function config(): HasMany
    {
        return $this->hasMany(TlHelpAppConfigAppConfig::class, 'config');
    }
    public function data(): HasMany
    {
        return $this->hasMany(TlInputAppEventInputAppEvent::class, 'data');
    }
    public function value(): HasMany
    {
        return $this->hasMany(TlJSONObjectValueJsonObjectValue::class, 'tl_value');
    }
}
