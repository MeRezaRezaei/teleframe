<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type InputSecureValue (spec §4.1). */
final class TlInputSecureValue extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_secure_value_input_secure_value';

    protected $guarded = [];
}
