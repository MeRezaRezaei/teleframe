<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for nearestDc of NearestDc (crc32 8e1a1775). */
final class TlNearestDcNearestDc extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_nearest_dc_nearest_dc';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'country' => 'string',
        'this_dc' => 'int',
        'nearest_dc' => 'int',
    ];
}
