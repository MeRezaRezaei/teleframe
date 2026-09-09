<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpCountryCountryCountry_codes;

/** Constructor model for help.country of help.Country (crc32 c3878e23). */
final class TlHelpCountryCountry extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_country_country';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'hidden' => 'bool',
        'iso2' => 'string',
        'default_name' => 'string',
        'name' => 'string',
    ];

    public function countryCodes(): HasMany
    {
        return $this->tlChild(TlHelpCountryCountryCountry_codes::class);
    }
}
