<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpCountryCodeCountryCodePatterns;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpCountryCodeCountryCodePrefixes;

/** Constructor model for help.countryCode of help.CountryCode (crc32 4203c5ef). */
final class TlHelpCountryCodeCountryCode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_country_code_country_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'country_code' => 'string',
    ];

    public function prefixes(): HasMany
    {
        return $this->tlChild(TlHelpCountryCodeCountryCodePrefixes::class);
    }
    public function patterns(): HasMany
    {
        return $this->tlChild(TlHelpCountryCodeCountryCodePatterns::class);
    }
}
