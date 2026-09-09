<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param translation (table tl_input_secure_value_input_secure_value__translation). */
final class TlInputSecureValueInputSecureValueTranslation extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_secure_value_input_secure_value__translation';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
