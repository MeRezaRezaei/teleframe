<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for boolFalse of Bool (crc32 bc799737). */
final class TlBoolBoolFalse extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bool_bool_false';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
